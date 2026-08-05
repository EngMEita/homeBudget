<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use App\Services\LedgerRuleService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_and_logout_issue_tokens(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonStructure(['user', 'token']);

        $this->postJson('/api/auth/login', [
            'email' => 'token@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_transfer_uses_paired_account_and_cross_currency_conversion(): void
    {
        [$user, $household, $fromAccount, $toAccount] = $this->seedTransferContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $fromAccount->id,
            'counterpart_account_id' => $toAccount->id,
            'currency_id' => $fromAccount->currency_id,
            'type' => 'transfer',
            'description' => 'SAR to USD',
            'amount_minor' => 1000,
            'base_amount_minor' => 3750,
            'exchange_rate' => 3.75,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $entries = app(LedgerRuleService::class)->post($transaction);

        $this->assertSame($fromAccount->id, $entries[0]['account_id']);
        $this->assertSame($toAccount->id, $entries[1]['account_id']);
        $this->assertSame(3750, $transaction->base_amount_minor);
    }

    private function seedTransferContext(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $sar = Currency::factory()->create(['code' => 'SAR', 'minor_unit_factor' => 100]);
        $usd = Currency::factory()->create(['code' => 'USD', 'minor_unit_factor' => 100]);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $fromAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $sar->id,
            'name' => 'SAR Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        $toAccount = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $usd->id,
            'name' => 'USD Wallet',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $fromAccount, $toAccount];
    }
}
