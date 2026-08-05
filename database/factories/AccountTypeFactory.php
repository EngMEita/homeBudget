<?php

namespace Database\Factories;

use App\Models\AccountType;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountTypeFactory extends Factory
{
    protected $model = AccountType::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => 'Cash',
            'code' => 'cash',
            'is_system' => false,
        ];
    }
}
