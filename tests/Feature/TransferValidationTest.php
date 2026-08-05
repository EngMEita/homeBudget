<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_requires_counterpart_account_and_exchange_rate_for_cross_currency(): void
    {
        [$user, $household, $sarAccount, $usdAccount] = $this->seedAccounts();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/transactions", [
                'account_id' => $sarAccount->id,
                'counterpart_account_id' => $usdAccount->id,
                'currency_id' => $sarAccount->currency_id,
                'type' => 'transfer',
                'description' => 'Missing exchange rate',
                'amount_minor' => 1000,
                'transaction_date' => now()->toDateString(),
                'created_by' => $user->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exchange_rate']);
    }

    public function test_transfer_rejects_same_account(): void
    {
        [$user, $household, $sarAccount] = $this->seedSingleAccount();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/transactions", [
                'account_id' => $sarAccount->id,
                'counterpart_account_id' => $sarAccount->id,
                'currency_id' => $sarAccount->currency_id,
                'type' => 'transfer',
                'description' => 'Invalid transfer',
                'amount_minor' => 1000,
                'transaction_date' => now()->toDateString(),
                'created_by' => $user->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['counterpart_account_id']);
    }

    private function seedAccounts(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $sar = Currency::factory()->create(['code' => 'SAR']);
        $usd = Currency::factory()->create(['code' => 'USD']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $sarAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $sar->id,
            'name' => 'SAR Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        $usdAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $usd->id,
            'name' => 'USD Wallet',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $sarAccount, $usdAccount];
    }

    private function seedSingleAccount(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $sar = Currency::factory()->create(['code' => 'SAR']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $sarAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $sar->id,
            'name' => 'SAR Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $sarAccount];
    }
}
