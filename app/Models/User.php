<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class, 'household_users')
            ->withPivot([
                'role',
                'can_view_balances',
                'can_create_transactions',
                'can_view_transactions',
            ])
            ->withTimestamps();
    }

    public function householdInvitations(): BelongsToMany
    {
        return $this->belongsToMany(Household::class, 'household_invitations', 'invited_user_id', 'household_id');
    }
}
