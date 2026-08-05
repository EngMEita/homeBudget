<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReceiptAllocationFactory extends Factory
{
    protected $model = ReceiptAllocation::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'receipt_id' => Receipt::factory(),
            'category_id' => Category::factory(),
            'amount_minor' => 500,
            'beneficiary_user_id' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
            'notes' => null,
            'version' => 1,
        ];
    }
}
