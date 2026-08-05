<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_household_scoped_and_can_be_marked_read(): void
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

        $visible = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'household_id' => $household->id,
                'title' => 'Upcoming bill',
                'message' => 'Rent is due',
            ],
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'household_id' => $household->id + 999,
                'title' => 'Hidden',
            ],
        ]);

        $this->actingAs($user)
            ->getJson("/api/households/{$household->id}/notifications")
            ->assertOk()
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonMissing(['title' => 'Hidden']);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/notifications/{$visible->id}/read")
            ->assertOk()
            ->assertJsonPath('read', true);

        $this->assertNotNull($visible->fresh()->read_at);
    }
}
