<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Household;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionEntryFactory extends Factory
{
    protected $model = TransactionEntry::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'transaction_id' => Transaction::factory(),
            'account_id' => Account::factory(),
            'household_id' => Household::factory(),
            'currency_id' => Currency::factory(),
            'amount_minor' => 100,
            'direction' => 'debit',
            'entry_type' => 'regular',
            'description' => null,
            'metadata' => [],
        ];
    }
}
