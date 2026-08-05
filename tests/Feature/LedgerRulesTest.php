<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\ReceiptAllocation;
use App\Models\Receipt;
use App\Models\User;
use App\Services\LedgerRuleService;
use App\Services\ReceiptCompletionService;
use App\Services\ReceiptService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_posting_creates_balanced_entries(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'transfer',
            'description' => 'Move money',
            'amount_minor' => 1000,
            'base_amount_minor' => 1000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $entries = app(LedgerRuleService::class)->post($transaction);

        $this->assertCount(2, $entries);
        $this->assertSame(-1000, $entries[0]['amount_minor']);
        $this->assertSame(1000, $entries[1]['amount_minor']);
    }

    public function test_refund_posting_credits_the_original_account(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'refund',
            'description' => 'Refund',
            'amount_minor' => -500,
            'base_amount_minor' => -500,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $entries = app(LedgerRuleService::class)->post($transaction);

        $this->assertSame('credit', $entries[0]['direction']);
        $this->assertSame(500, $entries[0]['amount_minor']);
    }

    public function test_exchange_rate_defaults_base_amount_when_missing(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();

        $transaction = app(TransactionService::class)->create($household, [
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'expense',
            'description' => 'Foreign spend',
            'amount_minor' => 100,
            'exchange_rate' => 3.75,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->assertSame(375, $transaction->base_amount_minor);
    }

    public function test_receipt_completion_rejects_allocation_overrun(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $category = Category::factory()->create(['household_id' => $household->id]);

        $receipt = app(ReceiptService::class)->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'paid_by_user_id' => $user->id,
            'total_minor_amount' => 1000,
            'base_currency_minor_amount' => 1000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'allocations' => [
                ['category_id' => $category->id, 'amount_minor' => 700],
            ],
        ]);

        ReceiptAllocation::create([
            'receipt_id' => $receipt->id,
            'category_id' => $category->id,
            'amount_minor' => 400,
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ReceiptCompletionService::class)->complete($receipt);
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
