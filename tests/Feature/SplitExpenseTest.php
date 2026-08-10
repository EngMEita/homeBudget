<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\PaymentLeg;
use App\Models\Transaction;
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

    public function test_split_expense_supports_independent_exchange_rate_per_source(): void
    {
        [$user, $household, $sar, $sarAccount, $usdAccount] = $this->setupMixedCurrencyHousehold();

        $response = $this->actingAs($user)->postJson("/api/households/{$household->id}/transactions/split-expense", [
            'currency_id' => $sar->id,
            'amount_minor' => 100000,
            'base_amount_minor' => 100000,
            'description' => 'Mixed currency bill',
            'transaction_date' => '2026-08-10',
            'payment_legs' => [
                ['account_id' => $sarAccount->id, 'amount_minor' => 62500, 'base_amount_minor' => 62500],
                ['account_id' => $usdAccount->id, 'amount_minor' => 10000, 'base_amount_minor' => 37500, 'exchange_rate' => '3.75', 'exchange_rate_source' => 'manual', 'exchange_rate_date' => '2026-08-10'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount_minor', 100000)
            ->assertJsonPath('data.base_amount_minor', 100000)
            ->assertJsonCount(2, 'data.payment_legs');

        $usdLeg = PaymentLeg::query()->where('account_id', $usdAccount->id)->firstOrFail();
        $this->assertSame(10000, $usdLeg->amount_minor);
        $this->assertSame(37500, $usdLeg->base_amount_minor);
        $this->assertSame('3.75', $usdLeg->exchange_rate);
        $this->assertSame('manual', $usdLeg->exchange_rate_source);
    }

    public function test_payment_legs_can_be_edited_safely_with_mixed_currencies(): void
    {
        [$user, $household, $sar, $sarAccount, $usdAccount] = $this->setupMixedCurrencyHousehold();
        $transaction = $this->createMixedCurrencyExpense($user, $household, $sar, $sarAccount, $usdAccount);

        $response = $this->actingAs($user)->putJson("/api/households/{$household->id}/transactions/{$transaction->id}/payment-legs", [
            'version' => 1,
            'payment_legs' => [
                ['account_id' => $sarAccount->id, 'amount_minor' => 25000, 'base_amount_minor' => 25000],
                ['account_id' => $usdAccount->id, 'amount_minor' => 20000, 'base_amount_minor' => 75000, 'exchange_rate' => '3.75', 'exchange_rate_source' => 'manual', 'exchange_rate_date' => '2026-08-10'],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.version', 2)->assertJsonCount(2, 'data.payment_legs');
        $this->assertSame(100000, PaymentLeg::query()->where('transaction_id', $transaction->id)->sum('base_amount_minor'));

        $this->actingAs($user)->putJson("/api/households/{$household->id}/transactions/{$transaction->id}/payment-legs", [
            'version' => 1,
            'payment_legs' => [
                ['account_id' => $sarAccount->id, 'amount_minor' => 50000, 'base_amount_minor' => 50000],
                ['account_id' => $usdAccount->id, 'amount_minor' => 10000, 'base_amount_minor' => 50000, 'exchange_rate' => '5'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('version');
    }

    public function test_partial_refund_is_linked_to_original_expense_and_cannot_exceed_remaining_amount(): void
    {
        [$user, $household, $sar, $sarAccount, $usdAccount] = $this->setupMixedCurrencyHousehold();
        $transaction = $this->createMixedCurrencyExpense($user, $household, $sar, $sarAccount, $usdAccount);

        $this->actingAs($user)->postJson("/api/households/{$household->id}/transactions/{$transaction->id}/refunds", [
            'account_id' => $sarAccount->id,
            'amount_minor' => 30000,
            'transaction_date' => '2026-08-11',
            'description' => 'Returned item',
        ])->assertCreated()->assertJsonPath('data.type', 'refund')->assertJsonPath('data.amount_minor', 30000);

        $refund = Transaction::query()->where('type', 'refund')->firstOrFail();
        $this->assertSame($transaction->id, $refund->metadata['original_transaction_id']);

        $this->actingAs($user)->postJson("/api/households/{$household->id}/transactions/{$transaction->id}/refunds", [
            'account_id' => $sarAccount->id,
            'amount_minor' => 80000,
            'transaction_date' => '2026-08-12',
        ])->assertStatus(422)->assertJsonValidationErrors('amount_minor');
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

    private function setupMixedCurrencyHousehold(): array
    {
        [$user, $household, $sar, $sarAccount] = $this->setupHousehold();
        $usd = Currency::factory()->create(['code' => 'USD']);
        $type = AccountType::query()->where('household_id', $household->id)->firstOrFail();
        $usdAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $type->id,
            'currency_id' => $usd->id,
            'name' => 'USD Card',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $sar, $sarAccount, $usdAccount];
    }

    private function createMixedCurrencyExpense(User $user, Household $household, Currency $sar, Account $sarAccount, Account $usdAccount): Transaction
    {
        $this->actingAs($user)->postJson("/api/households/{$household->id}/transactions/split-expense", [
            'currency_id' => $sar->id,
            'amount_minor' => 100000,
            'base_amount_minor' => 100000,
            'description' => 'Mixed currency bill',
            'transaction_date' => '2026-08-10',
            'payment_legs' => [
                ['account_id' => $sarAccount->id, 'amount_minor' => 62500, 'base_amount_minor' => 62500],
                ['account_id' => $usdAccount->id, 'amount_minor' => 10000, 'base_amount_minor' => 37500, 'exchange_rate' => '3.75', 'exchange_rate_source' => 'manual', 'exchange_rate_date' => '2026-08-10'],
            ],
        ])->assertCreated();

        return Transaction::query()->where('description', 'Mixed currency bill')->firstOrFail();
    }
}
