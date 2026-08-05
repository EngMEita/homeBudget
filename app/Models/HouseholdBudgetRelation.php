<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HouseholdBudgetRelation
{
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
