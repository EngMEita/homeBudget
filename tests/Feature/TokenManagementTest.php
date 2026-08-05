<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_listing_rotation_and_revocation_work(): void
    {
        $user = User::factory()->create([
            'email' => 'dev@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('phone')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/tokens')
            ->assertOk()
            ->assertJsonPath('tokens.0.name', 'phone');

        $rotated = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/tokens/rotate')
            ->assertOk()
            ->json('token');

        $this->assertNotEmpty($rotated);

        $this->withHeader('Authorization', "Bearer {$rotated}")
            ->deleteJson('/api/auth/tokens')
            ->assertOk();
    }
}
