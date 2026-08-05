<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;

class LedgerRuleService
{
    public function createEntries(Transaction $transaction): array
    {
        return match ($transaction->type) {
            'transfer' => $this->createTransferEntries($transaction),
            'refund' => $this->createRefundEntries($transaction),
            default => $this->createSingleEntry($transaction),
        };
    }

    public function post(Transaction $transaction): array
    {
        return DB::transaction(function () use ($transaction): array {
            $entries = $this->createEntries($transaction);

            foreach ($entries as $entry) {
                TransactionEntry::create($entry);
            }

            $transaction->forceFill([
                'status' => 'confirmed',
                'version' => $transaction->version + 1,
            ])->save();

            return $entries;
        });
    }

    private function createSingleEntry(Transaction $transaction): array
    {
        return [
            [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'household_id' => $transaction->household_id,
                'currency_id' => $transaction->currency_id,
                'amount_minor' => $transaction->amount_minor,
                'direction' => $transaction->amount_minor >= 0 ? 'credit' : 'debit',
                'entry_type' => $transaction->type,
                'description' => $transaction->description,
                'metadata' => $transaction->metadata ?? [],
            ],
        ];
    }

    private function createTransferEntries(Transaction $transaction): array
    {
        $amount = abs((int) $transaction->amount_minor);
        $baseAmount = abs((int) $transaction->base_amount_minor);
        $fee = abs((int) ($transaction->transfer_fee_minor ?? 0));
        $fromAccount = Account::query()->findOrFail($transaction->account_id);
        $toAccount = $transaction->counterpart_account_id
            ? Account::query()->findOrFail($transaction->counterpart_account_id)
            : $fromAccount;

        return [
            [
                'transaction_id' => $transaction->id,
                'account_id' => $fromAccount->id,
                'household_id' => $transaction->household_id,
                'currency_id' => $transaction->currency_id,
                'amount_minor' => -1 * $amount,
                'direction' => 'debit',
                'entry_type' => 'transfer_out',
                'description' => $transaction->description,
                'metadata' => ['base_amount_minor' => $baseAmount, 'counterpart_account_id' => $toAccount->id, 'fee_minor' => $fee],
            ],
            [
                'transaction_id' => $transaction->id,
                'account_id' => $toAccount->id,
                'household_id' => $transaction->household_id,
                'currency_id' => $transaction->currency_id,
                'amount_minor' => max(0, $amount - $fee),
                'direction' => 'credit',
                'entry_type' => 'transfer_in',
                'description' => $transaction->description,
                'metadata' => ['base_amount_minor' => $baseAmount, 'counterpart_account_id' => $fromAccount->id, 'fee_minor' => $fee],
            ],
        ];
    }

    private function createRefundEntries(Transaction $transaction): array
    {
        return [
            [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'household_id' => $transaction->household_id,
                'currency_id' => $transaction->currency_id,
                'amount_minor' => abs((int) $transaction->amount_minor),
                'direction' => 'credit',
                'entry_type' => 'refund',
                'description' => $transaction->description,
                'metadata' => $transaction->metadata ?? [],
            ],
        ];
    }
}
