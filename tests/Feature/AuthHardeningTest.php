<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirmation_requires_valid_current_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->actingAs($user)
            ->postJson('/api/auth/confirm-password', ['password' => 'wrong-password'])
            ->assertUnprocessable();

        $this->actingAs($user)
            ->postJson('/api/auth/confirm-password', ['password' => 'secret-password'])
            ->assertOk()
            ->assertJsonPath('confirmed', true);
    }

    public function test_password_reset_changes_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com', 'password' => 'old-password']);
        $user->createToken('phone');
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertSame(0, $user->tokens()->count());
        $this->postJson('/api/auth/login', [
            'email' => 'reset@example.com',
            'password' => 'new-password',
        ])->assertOk();
    }
}
