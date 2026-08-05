<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Household;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'client_uuid' => (string) Str::uuid(),
            'household_id' => Household::factory(),
            'transaction_id' => null,
            'account_id' => Account::factory(),
            'currency_id' => Currency::factory(),
            'merchant_id' => null,
            'paid_by_user_id' => User::factory(),
            'total_minor_amount' => 1000,
            'base_currency_minor_amount' => 1000,
            'exchange_rate' => null,
            'transaction_date' => now()->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
            'receipt_status' => 'open',
            'categorization_status' => 'uncategorized',
            'receipt_number' => null,
            'notes' => null,
            'version' => 1,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
