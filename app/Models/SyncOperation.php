<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SyncOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'household_id',
        'user_id',
        'client_uuid',
        'operation_type',
        'status',
        'payload',
        'result',
        'conflict_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $operation): void {
            $operation->uuid ??= (string) Str::uuid();
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
