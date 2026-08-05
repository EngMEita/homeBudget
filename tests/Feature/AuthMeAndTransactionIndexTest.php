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

class AuthMeAndTransactionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_me_returns_primary_household(): void
    {
        [$user, $household] = $this->seedMembership();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('household.id', $household->id)
            ->assertJsonPath('household.name', $household->name);
    }

    public function test_transaction_index_is_household_scoped(): void
    {
        [$user, $household, $account] = $this->seedMembershipAndAccount();
        $otherCurrency = Currency::factory()->create(['code' => 'USD']);
        $otherAccountType = AccountType::factory()->create(['household_id' => $household->id, 'name' => 'Wallet', 'code' => 'wallet']);
        $otherAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $otherAccountType->id,
            'currency_id' => $otherCurrency->id,
            'name' => 'USD Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Scoped history row',
            'amount_minor' => 900,
            'base_amount_minor' => 900,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        Transaction::create([
            'household_id' => $household->id,
            'account_id' => $otherAccount->id,
            'currency_id' => $otherCurrency->id,
            'type' => 'income',
            'status' => 'confirmed',
            'description' => 'Filtered out row',
            'amount_minor' => 450,
            'base_amount_minor' => 450,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/transactions?type=expense&currency_id={$account->currency_id}&per_page=10")
            ->assertOk()
            ->assertJsonPath('data.0.id', $transaction->id)
            ->assertJsonPath('data.0.description', 'Scoped history row')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_transaction_index_paginates_and_exports_csv(): void
    {
        [$user, $household, $account] = $this->seedMembershipAndAccount();

        collect(range(1, 12))->each(function (int $index) use ($household, $account, $user): void {
            Transaction::create([
                'household_id' => $household->id,
                'account_id' => $account->id,
                'currency_id' => $account->currency_id,
                'type' => 'expense',
                'status' => 'confirmed',
                'description' => "Row {$index}",
                'amount_minor' => 100 + $index,
                'base_amount_minor' => 100 + $index,
                'transaction_date' => now()->subDays($index)->toDateString(),
                'created_by' => $user->id,
            ]);
        });

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/transactions?per_page=10")
            ->assertOk()
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(10, 'data');

        $response = $this->actingAs($user)
            ->get("/api/households/{$household->id}/transactions/export?type=expense");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('description', $response->streamedContent());
        $this->assertStringContainsString('Row 1', $response->streamedContent());
    }

    private function seedMembership(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        return [$user, $household];
    }

    private function seedMembershipAndAccount(): array
    {
        [$user, $household] = $this->seedMembership();
        $currency = Currency::factory()->create(['code' => 'SAR']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);

        $account = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $account];
    }
}
