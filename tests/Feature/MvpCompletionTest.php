<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Debt;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\RecurringRule;
use App\Models\SavingsGoal;
use App\Models\UpcomingBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MvpCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_rule_creates_upcoming_bill_and_audit_log(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/recurring-rules", [
                'account_id' => $account->id,
                'currency_id' => $currency->id,
                'name' => 'Internet bill',
                'type' => 'expense',
                'frequency' => 'monthly',
                'amount_minor' => 25000,
                'starts_on' => '2026-08-01',
                'next_run_on' => '2026-09-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Internet bill');

        $this->assertSame(1, RecurringRule::query()->where('household_id', $household->id)->count());
        $this->assertSame(1, UpcomingBill::query()->where('household_id', $household->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'recurring_rule.created']);
    }

    public function test_goal_contribution_updates_progress_and_completion_status(): void
    {
        [$user, $household, $_account, $currency] = $this->seedContext();

        $goalId = $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/savings-goals", [
                'currency_id' => $currency->id,
                'name' => 'Emergency fund',
                'target_minor_amount' => 10000,
                'target_date' => '2026-12-31',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/savings-goals/{$goalId}/contributions", [
                'amount_minor' => 10000,
                'contributed_on' => '2026-08-04',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_minor_amount', 10000)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress_percent', 100);

        $this->assertDatabaseHas('audit_logs', ['event' => 'savings_goal.contributed']);
    }

    public function test_debt_installment_cannot_exceed_remaining_amount(): void
    {
        [$user, $household, $_account, $currency] = $this->seedContext();

        $debtId = $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/debts", [
                'currency_id' => $currency->id,
                'name' => 'Family loan',
                'counterparty_name' => 'Relative',
                'principal_minor_amount' => 5000,
                'opened_on' => '2026-08-01',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/debts/{$debtId}/installments", [
                'principal_minor_amount' => 6000,
                'paid_on' => '2026-08-04',
            ])
            ->assertUnprocessable();

        $this->assertSame(5000, Debt::query()->findOrFail($debtId)->remaining_minor_amount);
    }

    public function test_goal_contribution_is_household_scoped(): void
    {
        [$user, $household, $_account, $currency] = $this->seedContext();
        [$otherUser, $otherHousehold] = $this->seedContext('other@example.com');

        $goal = SavingsGoal::create([
            'household_id' => $otherHousehold->id,
            'currency_id' => $currency->id,
            'created_by' => $otherUser->id,
            'name' => 'Other goal',
            'target_minor_amount' => 1000,
            'current_minor_amount' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/savings-goals/{$goal->id}/contributions", [
                'amount_minor' => 500,
                'contributed_on' => '2026-08-04',
            ])
            ->assertNotFound();
    }

    public function test_dashboard_includes_mvp_completion_counts(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        RecurringRule::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'created_by' => $user->id,
            'name' => 'Rent',
            'type' => 'expense',
            'frequency' => 'monthly',
            'amount_minor' => 100,
            'base_amount_minor' => 100,
            'starts_on' => '2026-08-01',
            'next_run_on' => '2026-09-01',
        ]);
        UpcomingBill::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'created_by' => $user->id,
            'name' => 'Rent',
            'amount_minor' => 100,
            'base_amount_minor' => 100,
            'due_on' => '2026-09-01',
        ]);
        AuditLog::create(['household_id' => $household->id, 'user_id' => $user->id, 'event' => 'test.event']);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('data.counts.recurring_rules', 1)
            ->assertJsonPath('data.counts.upcoming_bills', 1)
            ->assertJsonPath('data.audit_logs.0.event', 'test.event');
    }

    private function seedContext(string $email = 'owner@example.com'): array
    {
        $user = User::factory()->create(['email' => $email]);
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $currency = Currency::factory()->create(['code' => strtoupper(substr(md5($email), 0, 3))]);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);

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

        return [$user, $household, $account, $currency];
    }
}
