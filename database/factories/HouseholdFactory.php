<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->lastName() . ' Household',
            'base_currency_code' => 'SAR',
            'default_locale' => 'en',
            'owner_user_id' => User::factory(),
            'is_active' => true,
        ];
    }
}
