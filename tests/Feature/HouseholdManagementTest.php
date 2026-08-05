<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\HouseholdInvitation;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_household_index_returns_user_memberships(): void
    {
        [$user, $household] = $this->seedHousehold();

        $this->actingAs($user)
            ->getJson('/api/households')
            ->assertOk()
            ->assertJsonPath('data.0.id', $household->id)
            ->assertJsonPath('data.0.name', $household->name);
    }

    public function test_household_store_creates_owner_membership(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/households', [
                'name' => 'New Family',
                'base_currency_code' => 'SAR',
                'default_locale' => 'ar',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'New Family')
            ->assertJsonPath('data.default_locale', 'ar');

        $this->assertDatabaseHas('households', ['name' => 'New Family', 'owner_user_id' => $user->id]);
        $this->assertDatabaseHas('household_users', ['user_id' => $user->id, 'role' => HouseholdRole::Owner->value]);
    }

    public function test_household_members_can_be_invited_and_accepted(): void
    {
        [$owner, $household] = $this->seedHousehold();
        $spouse = User::factory()->create(['email' => 'spouse@example.com']);

        $inviteResponse = $this->actingAs($owner)
            ->postJson("/api/households/{$household->id}/members/invitations", [
                'email' => 'spouse@example.com',
                'role' => 'contributor',
            ])
            ->assertCreated();

        $token = $inviteResponse->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('household_invitations', [
            'household_id' => $household->id,
            'email' => 'spouse@example.com',
            'role' => 'contributor',
        ]);

        $this->actingAs($spouse)
            ->postJson("/api/invitations/{$token}/accept")
            ->assertOk()
            ->assertJsonPath('accepted', true);

        $this->assertDatabaseHas('household_users', [
            'household_id' => $household->id,
            'user_id' => $spouse->id,
            'role' => 'contributor',
        ]);
        $this->assertDatabaseHas('household_invitations', [
            'token' => $token,
        ]);
    }

    private function seedHousehold(): array
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
}
