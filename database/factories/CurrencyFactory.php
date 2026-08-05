<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => 'SAR',
            'name_en' => 'Saudi Riyal',
            'name_ar' => 'ريال سعودي',
            'symbol' => 'SAR',
            'decimal_places' => 2,
            'minor_unit_factor' => 100,
            'is_active' => true,
        ];
    }
}
