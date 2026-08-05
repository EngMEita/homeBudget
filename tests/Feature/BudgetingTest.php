<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_creation_and_summary_report_actual_spend(): void
    {
        [$user, $household, $account, $category] = $this->seedContext();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/budgets", [
                'name' => 'August budget',
                'starts_on' => '2026-08-01',
                'ends_on' => '2026-08-31',
                'lines' => [
                    ['category_id' => $category->id, 'planned_minor_amount' => 10000],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'August budget');

        Transaction::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'category_id' => $category->id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Grocery run',
            'amount_minor' => 2500,
            'base_amount_minor' => 2500,
            'transaction_date' => '2026-08-12',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/budgets")
            ->assertOk()
            ->assertJsonPath('budget.name', 'August budget')
            ->assertJsonPath('periods.0.lines.0.actual_minor_amount', 2500)
            ->assertJsonPath('periods.0.lines.0.remaining_minor_amount', 7500);
    }

    private function seedContext(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $currency = Currency::factory()->create(['code' => 'SAR']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);
        $category = Category::factory()->create(['household_id' => $household->id, 'type' => 'expense']);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $account = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $account, $category];
    }
}
