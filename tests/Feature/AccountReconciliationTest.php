<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_reconciliation_creates_confirmed_adjustment_for_difference(): void
    {
        [$user, $household, $account] = $this->seedContext();
        Transaction::factory()->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'type' => 'income',
            'status' => 'confirmed',
            'amount_minor' => 200,
            'base_amount_minor' => 200,
        ]);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/account-reconciliations", [
                'account_id' => $account->id,
                'statement_balance_minor' => 150,
                'reconciled_on' => '2026-08-05',
                'notes' => 'Statement match',
            ])
            ->assertCreated()
            ->assertJsonPath('data.previous_balance_minor', 200)
            ->assertJsonPath('data.statement_balance_minor', 150)
            ->assertJsonPath('data.difference_minor', -50);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'type' => 'adjustment',
            'status' => 'confirmed',
            'amount_minor' => -50,
        ]);
    }

    public function test_account_reconciliation_rejects_account_from_another_household(): void
    {
        [$user, $household, $account] = $this->seedContext();
        $otherAccount = Account::factory()->create([
            'currency_id' => $account->currency_id,
            'account_type_id' => $account->account_type_id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/account-reconciliations", [
                'account_id' => $otherAccount->id,
                'statement_balance_minor' => 0,
                'reconciled_on' => '2026-08-05',
            ])
            ->assertUnprocessable();
    }

    private function seedContext(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $currency = Currency::factory()->create(['code' => 'SAR']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);
        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);
        $account = Account::factory()->create([
            'household_id' => $household->id,
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
            'opening_balance_minor' => 0,
        ]);

        return [$user, $household, $account];
    }
}
