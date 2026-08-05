<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    public function create(array $data): Receipt
    {
        return DB::transaction(function () use ($data): Receipt {
            $account = Account::query()
                ->whereKey($data['account_id'])
                ->where('household_id', $data['household_id'])
                ->firstOrFail();

            $receipt = Receipt::create([
                'client_uuid' => $data['client_uuid'] ?? null,
                'household_id' => $data['household_id'],
                'transaction_id' => null,
                'account_id' => $account->getKey(),
                'currency_id' => $data['currency_id'],
                'merchant_id' => $data['merchant_id'] ?? null,
                'paid_by_user_id' => $data['paid_by_user_id'],
                'total_minor_amount' => (int) $data['total_minor_amount'],
                'base_currency_minor_amount' => (int) $data['base_currency_minor_amount'],
                'exchange_rate' => $data['exchange_rate'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'transaction_time' => $data['transaction_time'] ?? null,
                'receipt_status' => $data['receipt_status'] ?? 'open',
                'categorization_status' => $data['categorization_status'] ?? 'uncategorized',
                'receipt_number' => $data['receipt_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'version' => $data['version'] ?? 1,
                'created_by' => $data['created_by'],
                'updated_by' => $data['created_by'],
            ]);

            $this->assertAllocationsWithinTotal($receipt, $data['allocations'] ?? []);

            foreach ($data['allocations'] ?? [] as $allocation) {
                ReceiptAllocation::create([
                    'receipt_id' => $receipt->id,
                    'category_id' => $allocation['category_id'],
                    'amount_minor' => (int) $allocation['amount_minor'],
                    'beneficiary_user_id' => $allocation['beneficiary_user_id'] ?? null,
                    'created_by' => $data['created_by'],
                    'updated_by' => $data['created_by'],
                    'notes' => $allocation['notes'] ?? null,
                    'version' => 1,
                ]);
            }

            if (! empty($data['allocations'])) {
                $this->refreshCategorizationStatus($receipt);
            }

            return $receipt;
        });
    }

    public function update(Receipt $receipt, array $data): Receipt
    {
        return DB::transaction(function () use ($receipt, $data): Receipt {
            $receipt->forceFill([
                'total_minor_amount' => (int) ($data['total_minor_amount'] ?? $receipt->total_minor_amount),
                'base_currency_minor_amount' => (int) ($data['base_currency_minor_amount'] ?? $receipt->base_currency_minor_amount),
                'receipt_status' => $data['receipt_status'] ?? $receipt->receipt_status,
                'categorization_status' => $data['categorization_status'] ?? $receipt->categorization_status,
                'receipt_number' => $data['receipt_number'] ?? $receipt->receipt_number,
                'notes' => $data['notes'] ?? $receipt->notes,
                'version' => $receipt->version + 1,
            ])->save();

            if (array_key_exists('allocations', $data)) {
                $this->assertAllocationsWithinTotal($receipt, $data['allocations']);

                $receipt->allocations()->delete();

                foreach ($data['allocations'] as $allocation) {
                    ReceiptAllocation::create([
                        'receipt_id' => $receipt->id,
                        'category_id' => $allocation['category_id'],
                        'amount_minor' => (int) $allocation['amount_minor'],
                        'beneficiary_user_id' => $allocation['beneficiary_user_id'] ?? null,
                        'created_by' => $receipt->created_by,
                        'updated_by' => $receipt->updated_by,
                        'notes' => $allocation['notes'] ?? null,
                        'version' => 1,
                    ]);
                }

                $this->refreshCategorizationStatus($receipt);
            }

            return $receipt;
        });
    }

    public function categorizedTotal(Receipt $receipt): int
    {
        return (int) $receipt->allocations()->whereNull('deleted_at')->sum('amount_minor');
    }

    public function remainingUncategorizedAmount(Receipt $receipt): int
    {
        return max(0, (int) $receipt->total_minor_amount - $this->categorizedTotal($receipt));
    }

    public function syncTransactionEntry(Receipt $receipt): Transaction
    {
        return Transaction::query()->firstOrCreate(
            ['uuid' => $receipt->transaction_id],
            [
                'client_uuid' => $receipt->client_uuid,
                'household_id' => $receipt->household_id,
                'account_id' => $receipt->account_id,
                'currency_id' => $receipt->currency_id,
                'category_id' => null,
                'created_by' => $receipt->created_by,
                'updated_by' => $receipt->updated_by,
                'type' => 'receipt',
                'status' => 'confirmed',
                'description' => $receipt->notes,
                'amount_minor' => -1 * (int) $receipt->total_minor_amount,
                'base_amount_minor' => -1 * (int) $receipt->base_currency_minor_amount,
                'exchange_rate' => $receipt->exchange_rate,
                'exchange_rate_source' => null,
                'transaction_date' => $receipt->transaction_date,
                'metadata' => ['receipt_id' => $receipt->id],
                'version' => 1,
            ]
        );
    }

    private function assertAllocationsWithinTotal(Receipt $receipt, array $allocations): void
    {
        $total = array_sum(array_map(static fn (array $allocation): int => (int) $allocation['amount_minor'], $allocations));

        if ($total > (int) $receipt->total_minor_amount) {
            throw ValidationException::withMessages([
                'allocations' => ['Allocation total cannot exceed the receipt total.'],
            ]);
        }
    }

    private function refreshCategorizationStatus(Receipt $receipt): void
    {
        $categorized = $this->categorizedTotal($receipt->refresh());
        $status = match (true) {
            $categorized === 0 => 'uncategorized',
            $categorized === (int) $receipt->total_minor_amount => 'fully_categorized',
            default => 'partially_categorized',
        };

        $receipt->forceFill([
            'categorization_status' => $status,
            'version' => $receipt->version + 1,
        ])->save();
    }
}
