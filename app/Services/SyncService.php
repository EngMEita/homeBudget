<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Receipt;
use App\Models\ReceiptAttachment;
use App\Models\SyncOperation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncService
{
    public function apply(Household $household, int $userId, array $operations): array
    {
        return collect($operations)->map(function (array $operation) use ($household, $userId): array {
            return DB::transaction(function () use ($household, $userId, $operation): array {
                $existingOperation = SyncOperation::query()
                    ->where('household_id', $household->id)
                    ->where('client_uuid', $operation['client_uuid'])
                    ->where('operation_type', $operation['operation_type'])
                    ->first();

                if ($existingOperation) {
                    if ($existingOperation->payload !== $operation['payload']) {
                        return [
                            'client_uuid' => $operation['client_uuid'],
                            'status' => 'conflict',
                            'result' => $existingOperation->result,
                            'conflict_reason' => 'The client UUID was already synced with a different payload.',
                            'client_payload' => $operation['payload'],
                            'server_payload' => $existingOperation->payload,
                            'server_result' => $existingOperation->result,
                        ];
                    }

                    return [
                        'client_uuid' => $operation['client_uuid'],
                        'status' => $existingOperation->status,
                        'result' => $existingOperation->result,
                        'conflict_reason' => $existingOperation->conflict_reason,
                    ];
                }

                $syncOperation = SyncOperation::create([
                    'household_id' => $household->id,
                    'user_id' => $userId,
                    'client_uuid' => $operation['client_uuid'],
                    'operation_type' => $operation['operation_type'],
                    'status' => 'pending',
                    'payload' => $operation['payload'],
                ]);

                if ($operation['operation_type'] === 'transaction.create') {
                    return $this->applyTransactionCreate($household, $userId, $operation, $syncOperation);
                }

                if ($operation['operation_type'] === 'transaction.update') {
                    return $this->applyTransactionUpdate($household, $userId, $operation, $syncOperation);
                }

                if ($operation['operation_type'] === 'transaction.delete') {
                    return $this->applyTransactionDelete($household, $operation, $syncOperation);
                }

                if ($operation['operation_type'] === 'receipt.create') {
                    return $this->applyReceiptCreate($household, $userId, $operation, $syncOperation);
                }

                if ($operation['operation_type'] === 'receipt.attachment.create') {
                    return $this->applyReceiptAttachmentCreate($household, $userId, $operation, $syncOperation);
                }

                return $this->markConflict($syncOperation, 'Unsupported sync operation type.');
            });
        })->values()->all();
    }

    private function applyTransactionCreate(Household $household, int $userId, array $operation, SyncOperation $syncOperation): array
    {
        $existingTransaction = Transaction::query()
            ->where('household_id', $household->id)
            ->where('client_uuid', $operation['client_uuid'])
            ->first();

        if ($existingTransaction) {
            return $this->markConflict($syncOperation, 'A transaction already exists for this client UUID.');
        }

        $transaction = app(TransactionService::class)->create($household, array_merge(
            $operation['payload'],
            [
                'client_uuid' => $operation['client_uuid'],
                'created_by' => $userId,
            ]
        ));

        $syncOperation->forceFill([
            'status' => 'applied',
            'result' => ['transaction_id' => $transaction->id, 'version' => $transaction->version],
        ])->save();

        return [
            'client_uuid' => $operation['client_uuid'],
            'status' => 'applied',
            'result' => $syncOperation->result,
            'conflict_reason' => null,
        ];
    }

    private function applyTransactionUpdate(Household $household, int $userId, array $operation, SyncOperation $syncOperation): array
    {
        $transaction = Transaction::query()
            ->where('household_id', $household->id)
            ->whereKey($operation['payload']['transaction_id'])
            ->first();

        if (! $transaction) {
            return $this->markConflict($syncOperation, 'Transaction was not found for update.');
        }

        if ((int) $operation['payload']['version'] !== (int) $transaction->version) {
            return $this->markConflict($syncOperation, 'Transaction version conflict.', [
                'client_payload' => $operation['payload'],
                'server_payload' => $this->transactionSnapshot($transaction),
            ]);
        }

        $updated = app(TransactionService::class)->update($transaction, array_merge(
            $operation['payload'],
            ['updated_by' => $userId]
        ));

        $syncOperation->forceFill([
            'status' => 'applied',
            'result' => ['transaction_id' => $updated->id, 'version' => $updated->version],
        ])->save();

        return [
            'client_uuid' => $operation['client_uuid'],
            'status' => 'applied',
            'result' => $syncOperation->result,
            'conflict_reason' => null,
        ];
    }

    private function applyTransactionDelete(Household $household, array $operation, SyncOperation $syncOperation): array
    {
        $transaction = Transaction::query()
            ->where('household_id', $household->id)
            ->whereKey($operation['payload']['transaction_id'])
            ->first();

        if (! $transaction) {
            return $this->markConflict($syncOperation, 'Transaction was not found for deletion.');
        }

        if ((int) $operation['payload']['version'] !== (int) $transaction->version) {
            return $this->markConflict($syncOperation, 'Transaction version conflict.', [
                'client_payload' => $operation['payload'],
                'server_payload' => $this->transactionSnapshot($transaction),
            ]);
        }

        $transaction->delete();

        $syncOperation->forceFill([
            'status' => 'applied',
            'result' => ['transaction_id' => $transaction->id, 'deleted' => true],
        ])->save();

        return [
            'client_uuid' => $operation['client_uuid'],
            'status' => 'applied',
            'result' => $syncOperation->result,
            'conflict_reason' => null,
        ];
    }

    private function applyReceiptCreate(Household $household, int $userId, array $operation, SyncOperation $syncOperation): array
    {
        $existingReceipt = Receipt::query()
            ->where('household_id', $household->id)
            ->where('client_uuid', $operation['client_uuid'])
            ->first();

        if ($existingReceipt) {
            return $this->markConflict($syncOperation, 'A receipt already exists for this client UUID.');
        }

        $receipt = app(ReceiptService::class)->create(array_merge(
            $operation['payload'],
            [
                'household_id' => $household->id,
                'client_uuid' => $operation['client_uuid'],
                'created_by' => $userId,
            ]
        ));

        $syncOperation->forceFill([
            'status' => 'applied',
            'result' => ['receipt_id' => $receipt->id, 'version' => $receipt->version],
        ])->save();

        return [
            'client_uuid' => $operation['client_uuid'],
            'status' => 'applied',
            'result' => $syncOperation->result,
            'conflict_reason' => null,
        ];
    }

    private function applyReceiptAttachmentCreate(Household $household, int $userId, array $operation, SyncOperation $syncOperation): array
    {
        $receipt = Receipt::query()
            ->where('household_id', $household->id)
            ->where('client_uuid', $operation['payload']['receipt_client_uuid'])
            ->first();

        if (! $receipt) {
            return $this->markConflict($syncOperation, 'Receipt must be synced before its attachment.');
        }

        $encoded = $operation['payload']['file_base64']
            ?? implode('', $operation['payload']['file_base64_chunks'] ?? []);
        $binary = base64_decode($encoded, true);
        if ($binary === false) {
            return $this->markConflict($syncOperation, 'Attachment payload is not valid base64.');
        }

        $extension = pathinfo($operation['payload']['original_name'], PATHINFO_EXTENSION) ?: 'bin';
        $path = sprintf('receipts/%s/%s.%s', $receipt->uuid, $operation['client_uuid'], $extension);
        Storage::disk('local')->put($path, $binary);

        $attachment = ReceiptAttachment::create([
            'receipt_id' => $receipt->id,
            'uploaded_by_user_id' => $userId,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $operation['payload']['original_name'],
            'mime_type' => $operation['payload']['mime_type'],
            'size_bytes' => strlen($binary),
        ]);

        $syncOperation->forceFill([
            'status' => 'applied',
            'result' => ['attachment_id' => $attachment->id],
        ])->save();

        return [
            'client_uuid' => $operation['client_uuid'],
            'status' => 'applied',
            'result' => $syncOperation->result,
            'conflict_reason' => null,
        ];
    }

    private function markConflict(SyncOperation $operation, string $reason, array $details = []): array
    {
        $operation->forceFill([
            'status' => 'conflict',
            'conflict_reason' => $reason,
        ])->save();

        return [
            'client_uuid' => $operation->client_uuid,
            'status' => 'conflict',
            'result' => null,
            'conflict_reason' => $reason,
        ] + $details;
    }

    private function transactionSnapshot(Transaction $transaction): array
    {
        return [
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'currency_id' => $transaction->currency_id,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'description' => $transaction->description,
            'amount_minor' => $transaction->amount_minor,
            'base_amount_minor' => $transaction->base_amount_minor,
            'transaction_date' => optional($transaction->transaction_date)->toDateString(),
            'version' => $transaction->version,
        ];
    }
}
