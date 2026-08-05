<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Household;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'client_uuid' => (string) Str::uuid(),
            'household_id' => Household::factory(),
            'account_id' => Account::factory(),
            'currency_id' => Currency::factory(),
            'category_id' => Category::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'type' => 'expense',
            'status' => 'pending',
            'description' => fake()->sentence(3),
            'amount_minor' => 1000,
            'base_amount_minor' => 1000,
            'exchange_rate' => null,
            'exchange_rate_source' => null,
            'transaction_date' => now()->toDateString(),
            'metadata' => [],
            'version' => 1,
        ];
    }
}
