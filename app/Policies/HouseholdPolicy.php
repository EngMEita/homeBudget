<?php

namespace App\Policies;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;

class HouseholdPolicy
{
    public function view(User $user, Household $household): bool
    {
        return $this->hasMembership($user, $household);
    }

    public function manage(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [HouseholdRole::Owner, HouseholdRole::Administrator]);
    }

    public function createTransaction(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
            HouseholdRole::Contributor,
            HouseholdRole::Restricted,
        ]);
    }

    public function updateAccount(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
        ]);
    }

    public function deleteAccount(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
        ]);
    }

    public function updateTransaction(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
            HouseholdRole::Contributor,
        ]);
    }

    public function deleteTransaction(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
        ]);
    }

    public function viewReports(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
            HouseholdRole::Contributor,
            HouseholdRole::Viewer,
            HouseholdRole::Restricted,
        ]);
    }

    public function viewTransactions(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [
            HouseholdRole::Owner,
            HouseholdRole::Administrator,
            HouseholdRole::Contributor,
            HouseholdRole::Viewer,
            HouseholdRole::Restricted,
        ]);
    }

    public function export(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, [HouseholdRole::Owner, HouseholdRole::Administrator]);
    }

    private function hasMembership(User $user, Household $household): bool
    {
        return $user->households()->whereKey($household->getKey())->exists();
    }

    private function hasRole(User $user, Household $household, array $roles): bool
    {
        return $user->households()
            ->whereKey($household->getKey())
            ->wherePivotIn('role', array_map(fn (HouseholdRole $role) => $role->value, $roles))
            ->exists();
    }
}
