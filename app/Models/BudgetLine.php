<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BudgetLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'budget_period_id',
        'category_id',
        'planned_minor_amount',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $line): void {
            $line->uuid ??= (string) Str::uuid();
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
