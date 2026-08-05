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
use App\Services\BalanceService;
use App\Services\LedgerPostingService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceAndLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_service_uses_opening_balance_and_confirmed_transactions_only(): void
    {
        [$household, $account] = $this->seedAccountWithOwner();

        Transaction::factory()->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'status' => 'confirmed',
            'amount_minor' => -500,
            'base_amount_minor' => -500,
        ]);

        Transaction::factory()->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'status' => 'pending',
            'amount_minor' => -700,
            'base_amount_minor' => -700,
        ]);

        self::assertSame(9500, app(BalanceService::class)->accountBalance($account));
    }

    public function test_ledger_posting_confirms_transaction_and_bumps_version(): void
    {
        [$household, $account, $user] = $this->seedTransactionContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'type' => 'expense',
            'description' => 'Ledger test',
            'amount_minor' => 100,
            'base_amount_minor' => 100,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'version' => 1,
        ]);

        app(LedgerPostingService::class)->post($transaction->fresh());

        $transaction->refresh();

        self::assertSame('confirmed', $transaction->status);
        self::assertSame(2, $transaction->version);
    }

    private function seedAccountWithOwner(): array
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

        $account = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Cash',
            'opening_balance_minor' => 10000,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$household, $account];
    }

    private function seedTransactionContext(): array
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

        $account = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$household, $account, $user];
    }
}
