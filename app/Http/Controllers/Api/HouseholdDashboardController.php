<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HouseholdDashboardResource;
use App\Models\Household;
use Illuminate\Support\Facades\Gate;

class HouseholdDashboardController extends Controller
{
    public function show(Household $household): HouseholdDashboardResource
    {
        Gate::authorize('view', $household);

        $household->load(['owner']);
        $household->loadCount([
            'accounts',
            'transactions',
            'recurringRules',
            'upcomingBills',
            'savingsGoals',
            'debts',
        ]);
        $household->setRelation('latestUpcomingBills', $household->upcomingBills()->orderBy('due_on')->limit(5)->get());
        $household->setRelation('latestAuditLogs', $household->auditLogs()->latest('id')->limit(5)->get());

        return new HouseholdDashboardResource($household);
    }
}
