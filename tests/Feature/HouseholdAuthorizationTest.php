<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_account_and_transaction_within_household(): void
    {
        [$owner, $household, $currency, $accountType] = $this->seedHouseholdUser(HouseholdRole::Owner);

        $this->actingAs($owner)
            ->postJson("/api/households/{$household->id}/accounts", [
                'account_type_id' => $accountType->id,
                'currency_id' => $currency->id,
                'name' => 'Main Wallet',
                'opening_balance_minor' => 1500,
                'is_shared' => true,
                'is_active' => true,
            ])
            ->assertCreated();

        $account = Account::query()->forHousehold($household->id)->firstOrFail();

        [$contributor] = $this->seedHouseholdUser(HouseholdRole::Contributor, $household, $currency, $accountType);

        $this->actingAs($contributor)
            ->postJson("/api/households/{$household->id}/transactions", [
                'account_id' => $account->id,
                'currency_id' => $currency->id,
                'category_id' => null,
                'type' => 'expense',
                'description' => 'Groceries',
                'amount_minor' => 500,
                'base_amount_minor' => 500,
                'transaction_date' => now()->toDateString(),
                'version' => 1,
            ])
            ->assertCreated();
    }

    public function test_viewer_cannot_create_account(): void
    {
        [$user, $household, $currency, $accountType] = $this->seedHouseholdUser(HouseholdRole::Viewer);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/accounts", [
                'account_type_id' => $accountType->id,
                'currency_id' => $currency->id,
                'name' => 'Blocked Wallet',
                'opening_balance_minor' => 0,
            ])
            ->assertForbidden();
    }

    public function test_account_index_is_household_scoped(): void
    {
        [$user, $household, $currency, $accountType] = $this->seedHouseholdUser(HouseholdRole::Owner);
        $visibleAccount = Account::factory()->create([
            'household_id' => $household->id,
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
            'name' => 'Visible Wallet',
        ]);
        Account::factory()->create([
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
            'name' => 'Hidden Wallet',
        ]);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/accounts")
            ->assertOk()
            ->assertJsonPath('data.0.id', $visibleAccount->id)
            ->assertJsonMissing(['name' => 'Hidden Wallet'])
            ->assertJsonStructure(['account_types', 'currencies']);
    }

    public function test_category_index_is_household_scoped_and_viewer_cannot_create(): void
    {
        [$user, $household] = $this->seedHouseholdUser(HouseholdRole::Viewer);
        $visibleCategory = Category::factory()->create([
            'household_id' => $household->id,
            'name' => 'Groceries',
        ]);
        Category::factory()->create(['name' => 'Other Household Category']);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/categories")
            ->assertOk()
            ->assertJsonPath('data.0.id', $visibleCategory->id)
            ->assertJsonMissing(['name' => 'Other Household Category']);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/categories", [
                'name' => 'Blocked',
                'type' => 'expense',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_update_and_soft_delete_category(): void
    {
        [$user, $household] = $this->seedHouseholdUser(HouseholdRole::Owner);
        $category = Category::factory()->create(['household_id' => $household->id, 'name' => 'Old name']);

        $this->actingAs($user)
            ->putJson("/api/households/{$household->id}/categories/{$category->id}", [
                'name' => 'Updated name', 'type' => 'expense', 'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated name');

        $this->actingAs($user)
            ->deleteJson("/api/households/{$household->id}/categories/{$category->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_viewer_cannot_update_or_delete_category(): void
    {
        [$user, $household] = $this->seedHouseholdUser(HouseholdRole::Viewer);
        $category = Category::factory()->create(['household_id' => $household->id]);

        $this->actingAs($user)
            ->putJson("/api/households/{$household->id}/categories/{$category->id}", [
                'name' => 'Blocked', 'type' => 'expense',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/api/households/{$household->id}/categories/{$category->id}")
            ->assertForbidden();
    }

    public function test_cross_household_access_is_blocked(): void
    {
        [$user, $household] = $this->seedHouseholdUser(HouseholdRole::Owner);
        $otherHousehold = Household::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/households/{$otherHousehold->id}/dashboard")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/households/{$otherHousehold->id}/transactions", [
                'account_id' => 999999,
                'currency_id' => 999999,
                'type' => 'expense',
                'amount_minor' => 100,
                'base_amount_minor' => 100,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    private function seedHouseholdUser(HouseholdRole $role, ?Household $household = null, ?Currency $currency = null, ?AccountType $accountType = null): array
    {
        $user = User::factory()->create();
        $household ??= Household::factory()->create(['owner_user_id' => $user->id]);
        $currency ??= Currency::factory()->create(['code' => 'SAR']);
        $accountType ??= AccountType::factory()->create(['household_id' => $household->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => $role->value,
            'can_view_balances' => true,
            'can_create_transactions' => $role !== HouseholdRole::Viewer,
            'can_view_transactions' => true,
        ]);

        return [$user, $household, $currency, $accountType];
    }
}
