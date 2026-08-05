<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Household extends Model
{
    use HasFactory, SoftDeletes;
    use HouseholdBudgetRelation;

    protected $fillable = [
        'uuid',
        'name',
        'base_currency_code',
        'default_locale',
        'owner_user_id',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $household): void {
            $household->uuid ??= (string) Str::uuid();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'household_users')
            ->withPivot([
                'role',
                'can_view_balances',
                'can_create_transactions',
                'can_view_transactions',
            ])
            ->withTimestamps();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(HouseholdInvitation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function recurringRules(): HasMany
    {
        return $this->hasMany(RecurringRule::class);
    }

    public function upcomingBills(): HasMany
    {
        return $this->hasMany(UpcomingBill::class);
    }

    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function backupLogs(): HasMany
    {
        return $this->hasMany(BackupLog::class);
    }
}
