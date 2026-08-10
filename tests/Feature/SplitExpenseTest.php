<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\PaymentLeg;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_can_be_paid_from_multiple_accounts_without_duplicate_parent_expense(): void
    {
        [$user, $household, $currency, $first, $second] = $this->setupHousehold();

        $response = $this->actingAs($user)->postJson("/api/households/{$household->id}/transactions/split-expense", [
            'currency_id' => $currency->id,
            'amount_minor' => 45000,
            'description' => 'Family supermarket bill',
            'transaction_date' => now()->toDateString(),
            'payment_legs' => [
                ['account_id' => $first->id, 'amount_minor' => 15000],
                ['account_id' => $second->id, 'amount_minor' => 30000],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.amount_minor', 45000)->assertJsonCount(2, 'data.payment_legs');
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('payment_legs', 2);
        $this->assertSame(45000, PaymentLeg::query()->sum('amount_minor'));
    }

    public function test_split_expense_rejects_legs_that_do_not_equal_total(): void
    {
        [$user, $household, $currency, $first, $second] = $this->setupHousehold();

        $this->actingAs($user)->postJson("/api/households/{$household->id}/transactions/split-expense", [
            'currency_id' => $currency->id, 'amount_minor' => 45000, 'transaction_date' => now()->toDateString(),
            'payment_legs' => [['account_id' => $first->id, 'amount_minor' => 10000], ['account_id' => $second->id, 'amount_minor' => 30000]],
        ])->assertStatus(422)->assertJsonValidationErrors('payment_legs');
    }

    private function setupHousehold(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $currency = Currency::factory()->create(['code' => 'SAR']);
        $type = AccountType::factory()->create(['household_id' => $household->id]);
        HouseholdUser::create(['household_id' => $household->id, 'user_id' => $user->id, 'role' => HouseholdRole::Owner->value, 'can_view_balances' => true, 'can_create_transactions' => true, 'can_view_transactions' => true]);
        $attributes = ['household_id' => $household->id, 'account_type_id' => $type->id, 'currency_id' => $currency->id, 'opening_balance_minor' => 0, 'is_shared' => true, 'is_active' => true];
        return [$user, $household, $currency, Account::create($attributes + ['name' => 'Wallet']), Account::create($attributes + ['name' => 'Bank'])];
    }
}
