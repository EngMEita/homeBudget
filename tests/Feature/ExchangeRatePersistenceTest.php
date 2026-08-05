<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_rate_metadata_is_persisted_and_historical_amount_stays_stable(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'expense',
            'description' => 'Imported travel spend',
            'amount_minor' => 100,
            'exchange_rate' => 3.75,
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-08-01',
            'transaction_date' => '2026-08-02',
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->assertSame(375, $transaction->base_amount_minor);
        $this->assertSame('manual', $transaction->exchange_rate_source);
        $this->assertSame('2026-08-01', $transaction->exchange_rate_date?->format('Y-m-d'));

        Currency::query()->whereKey($currency->id)->update(['symbol' => 'US$']);
        $transaction->refresh();

        $this->assertSame(375, $transaction->base_amount_minor);
    }

    public function test_rounding_uses_half_up_for_minor_units(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'expense',
            'description' => 'Rounded conversion',
            'amount_minor' => 1,
            'exchange_rate' => 2.5,
            'transaction_date' => '2026-08-02',
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->assertSame(3, $transaction->base_amount_minor);
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
