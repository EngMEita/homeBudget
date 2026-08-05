<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'household_id' => Household::factory(),
            'parent_id' => null,
            'name' => fake()->word(),
            'type' => 'expense',
            'is_active' => true,
        ];
    }
}
