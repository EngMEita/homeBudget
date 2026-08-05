<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HouseholdInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'household_id',
        'invited_by_user_id',
        'invited_user_id',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invitation): void {
            $invitation->uuid ??= (string) Str::uuid();
            $invitation->token ??= Str::random(48);
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
}
