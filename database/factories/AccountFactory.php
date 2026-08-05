<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'household_id' => Household::factory(),
            'account_type_id' => AccountType::factory(),
            'currency_id' => Currency::factory(),
            'name' => fake()->words(2, true),
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ];
    }
}
