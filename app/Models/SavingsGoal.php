<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsGoal extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'currency_id',
        'created_by',
        'name',
        'target_minor_amount',
        'current_minor_amount',
        'target_date',
        'status',
    ];

    protected $casts = ['target_date' => 'date'];

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
