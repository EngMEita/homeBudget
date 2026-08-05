<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BudgetPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'budget_id',
        'starts_on',
        'ends_on',
        'status',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $period): void {
            $period->uuid ??= (string) Str::uuid();
        });
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }
}
