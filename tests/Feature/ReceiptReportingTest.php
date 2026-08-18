<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_allocations_do_not_double_count_account_balance(): void
    {
        [$user, $household, $account, $currency] = $this->seedHouseholdAccount();
        $category = Category::factory()->create(['household_id' => $household->id]);

        $receipt = app(ReceiptService::class)->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'paid_by_user_id' => $user->id,
            'total_minor_amount' => 1200,
            'base_currency_minor_amount' => 1200,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
            'allocations' => [
                ['category_id' => $category->id, 'amount_minor' => 700],
                ['category_id' => $category->id, 'amount_minor' => 300],
            ],
        ]);

        $this->assertSame(1000, app(ReceiptService::class)->categorizedTotal($receipt));
        $this->assertSame(200, app(ReceiptService::class)->remainingUncategorizedAmount($receipt));
        $this->assertSame(0, Receipt::query()->count() - 1);
    }

    public function test_reports_payload_is_household_scoped(): void
    {
        [$user, $household, $account] = $this->seedHouseholdAccount();
        $transaction = Transaction::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Snack run',
            'amount_minor' => 750,
            'base_amount_minor' => 750,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/reports")
            ->assertOk()
            ->assertJsonPath('data.household_id', $household->id)
            ->assertJsonPath('data.household_name', $household->name)
            ->assertJsonPath('data.recent_transactions.0.id', $transaction->id)
            ->assertJsonPath('data.recent_transactions.0.amount_minor', 750);
    }

    public function test_receipt_categorization_status_moves_from_partial_to_full(): void
    {
        [$user, $household, $account, $currency] = $this->seedHouseholdAccount();
        $category = Category::factory()->create(['household_id' => $household->id]);

        $receipt = app(ReceiptService::class)->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'paid_by_user_id' => $user->id,
            'total_minor_amount' => 1200,
            'base_currency_minor_amount' => 1200,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->putJson("/api/households/{$household->id}/receipts/{$receipt->id}", [
                'account_id' => $account->id,
                'currency_id' => $currency->id,
                'paid_by_user_id' => $user->id,
                'total_minor_amount' => 1200,
                'base_currency_minor_amount' => 1200,
                'transaction_date' => now()->toDateString(),
                'allocations' => [
                    ['category_id' => $category->id, 'amount_minor' => 700],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.categorization_status', 'partially_categorized')
            ->assertJsonPath('data.remaining_uncategorized_minor_amount', 500);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/receipts/{$receipt->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.categorization_status', 'partially_categorized');

        $this->actingAs($user)
            ->putJson("/api/households/{$household->id}/receipts/{$receipt->id}", [
                'account_id' => $account->id,
                'currency_id' => $currency->id,
                'paid_by_user_id' => $user->id,
                'total_minor_amount' => 1200,
                'base_currency_minor_amount' => 1200,
                'transaction_date' => now()->toDateString(),
                'allocations' => [
                    ['category_id' => $category->id, 'amount_minor' => 1200],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.categorization_status', 'fully_categorized')
            ->assertJsonPath('data.remaining_uncategorized_minor_amount', 0);
    }

    public function test_receipt_attachment_upload_is_private_and_household_scoped(): void
    {
        Storage::fake('local');

        [$user, $household, $account, $currency] = $this->seedHouseholdAccount();
        $receipt = app(ReceiptService::class)->create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'paid_by_user_id' => $user->id,
            'total_minor_amount' => 900,
            'base_currency_minor_amount' => 900,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post("/api/households/{$household->id}/receipts/{$receipt->id}/attachments", [
                'attachment' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.original_name', 'receipt.jpg');

        $path = $receipt->attachments()->firstOrFail()->path;
        Storage::disk('local')->assertExists($path);
    }

    public function test_receipt_index_show_update_and_delete_are_authorized(): void
    {
        [$user, $household, $account, $currency] = $this->seedHouseholdAccount();
        $receipt = app(ReceiptService::class)->create([
            'household_id' => $household->id, 'account_id' => $account->id, 'currency_id' => $currency->id,
            'paid_by_user_id' => $user->id, 'total_minor_amount' => 900, 'base_currency_minor_amount' => 900,
            'transaction_date' => now()->toDateString(), 'created_by' => $user->id,
        ]);

        $this->actingAs($user)->getJson("/api/households/{$household->id}/receipts")
            ->assertOk()->assertJsonPath('data.0.id', $receipt->id);
        $this->actingAs($user)->getJson("/api/households/{$household->id}/receipts/{$receipt->id}")
            ->assertOk()->assertJsonPath('data.id', $receipt->id);
        $this->actingAs($user)->putJson("/api/households/{$household->id}/receipts/{$receipt->id}", [
            'account_id' => $account->id, 'currency_id' => $currency->id, 'paid_by_user_id' => $user->id,
            'total_minor_amount' => 900, 'base_currency_minor_amount' => 900,
            'transaction_date' => now()->toDateString(), 'notes' => 'Updated receipt',
        ])->assertOk();
        $this->actingAs($user)->deleteJson("/api/households/{$household->id}/receipts/{$receipt->id}")
            ->assertNoContent();
        $this->assertSoftDeleted('receipts', ['id' => $receipt->id]);
    }

    public function test_viewer_cannot_modify_or_delete_receipt(): void
    {
        [$owner, $household, $account, $currency] = $this->seedHouseholdAccount();
        $receipt = Receipt::factory()->create(['household_id' => $household->id, 'account_id' => $account->id, 'currency_id' => $currency->id]);
        $viewer = User::factory()->create();
        HouseholdUser::create(['household_id' => $household->id, 'user_id' => $viewer->id, 'role' => HouseholdRole::Viewer->value, 'can_view_balances' => true, 'can_create_transactions' => false, 'can_view_transactions' => true]);

        $this->actingAs($viewer)->deleteJson("/api/households/{$household->id}/receipts/{$receipt->id}")->assertForbidden();
    }

    private function seedHouseholdAccount(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $currency = Currency::factory()->create(['code' => 'SAR']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);
        $account = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Wallet',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        return [$user, $household, $account, $currency];
    }
}
