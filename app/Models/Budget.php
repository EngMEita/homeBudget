<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'name',
        'period_type',
        'base_currency_code',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $budget): void {
            $budget->uuid ??= (string) Str::uuid();
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }
}
