<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_household_system_health(): void
    {
        [$user, $household] = $this->seedHousehold(HouseholdRole::Owner);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/health")
            ->assertOk()
            ->assertJsonPath('data.checks.0.name', 'database_integrity')
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'checked_at',
                    'checks' => [
                        ['name', 'status', 'message', 'meta'],
                    ],
                ],
            ]);
    }

    public function test_viewer_cannot_view_system_health(): void
    {
        [$user, $household] = $this->seedHousehold(HouseholdRole::Viewer);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/health")
            ->assertForbidden();
    }

    private function seedHousehold(HouseholdRole $role): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => $role->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        return [$user, $household];
    }
}
