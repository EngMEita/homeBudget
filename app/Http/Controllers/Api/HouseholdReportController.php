<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HouseholdReportResource;
use App\Models\Household;

class HouseholdReportController extends Controller
{
    public function show(Household $household): HouseholdReportResource
    {
        \Gate::authorize('viewReports', $household);

        $household->loadCount(['accounts', 'transactions', 'recurringRules', 'upcomingBills', 'savingsGoals', 'debts']);
        $household->setRelation(
            'recentTransactions',
            $household->transactions()
                ->latest('transaction_date')
                ->latest('id')
                ->limit(8)
                ->with('paymentLegs.account')
                ->get()
        );

        return new HouseholdReportResource($household);
    }
}
