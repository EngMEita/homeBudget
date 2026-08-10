<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Household;
use App\Models\Transaction;
use App\Services\LedgerRuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function create(Household $household, array $data): Transaction
    {
        return DB::transaction(function () use ($household, $data): Transaction {
            $account = Account::query()
                ->whereKey($data['account_id'])
                ->where('household_id', $household->getKey())
                ->firstOrFail();

            $baseAmount = $this->normalizeBaseAmount($data);

            return Transaction::create([
                'household_id' => $household->getKey(),
                'account_id' => $account->getKey(),
                'counterpart_account_id' => $data['counterpart_account_id'] ?? null,
                'currency_id' => $data['currency_id'],
                'category_id' => $data['category_id'] ?? null,
                'created_by' => $data['created_by'],
                'updated_by' => $data['created_by'],
                'type' => $data['type'],
                'status' => $data['status'] ?? 'pending',
                'description' => $data['description'] ?? null,
                'amount_minor' => (int) $data['amount_minor'],
                'base_amount_minor' => $baseAmount,
                'transfer_fee_minor' => (int) ($data['transfer_fee_minor'] ?? 0),
                'exchange_rate' => $data['exchange_rate'] ?? null,
                'exchange_rate_source' => $data['exchange_rate_source'] ?? null,
                'exchange_rate_date' => $data['exchange_rate_date'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'client_uuid' => $data['client_uuid'] ?? null,
                'version' => $data['version'] ?? 1,
                'metadata' => $data['metadata'] ?? [],
            ]);
        });
    }

    public function createSplitExpense(Household $household, array $data): Transaction
    {
        return DB::transaction(function () use ($household, $data): Transaction {
            if (! empty($data['client_uuid'])) {
                $existing = Transaction::query()->where('household_id', $household->id)->where('client_uuid', $data['client_uuid'])->with('paymentLegs.account')->first();
                if ($existing) return $existing;
            }
            $legs = collect($data['payment_legs']);
            $accounts = Account::query()->where('household_id', $household->id)->whereIn('id', $legs->pluck('account_id'))->get()->keyBy('id');
            if ($accounts->count() !== $legs->pluck('account_id')->unique()->count()) {
                throw ValidationException::withMessages(['payment_legs' => 'All payment accounts must belong to the household.']);
            }
            $currencyIds = $legs->map(fn ($leg) => $accounts[(int) $leg['account_id']]->currency_id)->unique();
            $totalMatches = $currencyIds->count() === 1
                ? $legs->sum(fn ($leg) => (int) $leg['amount_minor']) === (int) $data['amount_minor']
                : $legs->sum(fn ($leg) => (int) ($leg['base_amount_minor'] ?? 0)) === (int) ($data['base_amount_minor'] ?? $data['amount_minor']);
            if (! $totalMatches) {
                throw ValidationException::withMessages(['payment_legs' => 'Payment legs must equal the expense total.']);
            }

            $transaction = $this->create($household, $data + ['account_id' => $accounts->first()->id]);
            foreach ($legs as $leg) {
                $transaction->paymentLegs()->create([
                    'household_id' => $household->id,
                    'account_id' => $leg['account_id'],
                    'currency_id' => $accounts[(int) $leg['account_id']]->currency_id,
                    'amount_minor' => (int) $leg['amount_minor'],
                    'base_amount_minor' => (int) ($leg['base_amount_minor'] ?? $leg['amount_minor']),
                    'exchange_rate' => $leg['exchange_rate'] ?? null,
                    'exchange_rate_source' => $leg['exchange_rate_source'] ?? null,
                    'exchange_rate_date' => $leg['exchange_rate_date'] ?? null,
                ]);
            }
            return $transaction->load('paymentLegs.account');
        });
    }

    public function updatePaymentLegs(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data): Transaction {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ((int) $data['version'] !== (int) $locked->version) {
                throw ValidationException::withMessages(['version' => 'The transaction was changed by another user.']);
            }
            $legs = collect($data['payment_legs']);
            if ($legs->sum(fn ($leg) => (int) $leg['amount_minor']) !== (int) $locked->amount_minor) {
                throw ValidationException::withMessages(['payment_legs' => 'Payment legs must equal the expense total.']);
            }
            $accounts = Account::query()->where('household_id', $locked->household_id)->whereIn('id', $legs->pluck('account_id'))->get()->keyBy('id');
            if ($accounts->count() !== $legs->pluck('account_id')->unique()->count() || $accounts->pluck('currency_id')->unique()->count() !== 1 || (int) $accounts->first()->currency_id !== (int) $locked->currency_id) {
                throw ValidationException::withMessages(['payment_legs' => 'Payment accounts must belong to the household and use the transaction currency.']);
            }
            $locked->paymentLegs()->delete();
            foreach ($legs as $leg) $locked->paymentLegs()->create(['household_id' => $locked->household_id, 'account_id' => $leg['account_id'], 'currency_id' => $locked->currency_id, 'amount_minor' => $leg['amount_minor'], 'base_amount_minor' => $leg['amount_minor']]);
            $locked->increment('version');
            return $locked->load('paymentLegs.account');
        });
    }

    public function createPartialRefund(Transaction $original, array $data): Transaction
    {
        return DB::transaction(function () use ($original, $data): Transaction {
            $refunded = Transaction::query()->where('type', 'refund')->where('metadata->original_transaction_id', $original->id)->sum('amount_minor');
            if ($refunded + (int) $data['amount_minor'] > (int) $original->amount_minor) {
                throw ValidationException::withMessages(['amount_minor' => 'Refund cannot exceed the original expense amount.']);
            }
            $account = Account::query()->where('id', $data['account_id'])->where('household_id', $original->household_id)->firstOrFail();
            return $this->create($original->household, [
                'account_id' => $account->id, 'currency_id' => $original->currency_id, 'type' => 'refund', 'status' => 'confirmed',
                'amount_minor' => (int) $data['amount_minor'], 'base_amount_minor' => (int) $data['amount_minor'],
                'description' => $data['description'] ?? 'Partial refund', 'transaction_date' => $data['transaction_date'],
                'created_by' => $data['created_by'], 'metadata' => ['original_transaction_id' => $original->id],
            ]);
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $account = Account::query()
            ->whereKey($data['account_id'])
            ->where('household_id', $transaction->household_id)
            ->firstOrFail();

        $baseAmount = $this->normalizeBaseAmount($data);

        $transaction->forceFill([
            'account_id' => $account->getKey(),
            'counterpart_account_id' => $data['counterpart_account_id'] ?? $transaction->counterpart_account_id,
            'currency_id' => $data['currency_id'],
            'category_id' => $data['category_id'] ?? null,
            'updated_by' => $data['updated_by'],
            'type' => $data['type'],
            'status' => $data['status'] ?? $transaction->status,
            'description' => $data['description'] ?? null,
            'amount_minor' => (int) $data['amount_minor'],
            'base_amount_minor' => $baseAmount,
            'transfer_fee_minor' => (int) ($data['transfer_fee_minor'] ?? $transaction->transfer_fee_minor ?? 0),
            'exchange_rate' => $data['exchange_rate'] ?? null,
            'exchange_rate_source' => $data['exchange_rate_source'] ?? null,
            'exchange_rate_date' => $data['exchange_rate_date'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'version' => $transaction->version + 1,
            'metadata' => $data['metadata'] ?? $transaction->metadata ?? [],
        ])->save();

        return $transaction;
    }

    public function post(Transaction $transaction): array
    {
        return app(LedgerRuleService::class)->post($transaction);
    }

    private function normalizeBaseAmount(array $data): int
    {
        if (array_key_exists('base_amount_minor', $data) && $data['base_amount_minor'] !== null) {
            return (int) $data['base_amount_minor'];
        }

        if (array_key_exists('exchange_rate', $data) && $data['exchange_rate'] !== null) {
            return (int) round(((float) $data['amount_minor']) * (float) $data['exchange_rate'], 0, PHP_ROUND_HALF_UP);
        }

        return (int) $data['amount_minor'];
    }
}
