<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Household;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BudgetController extends Controller
{
    public function store(StoreBudgetRequest $request, Household $household, BudgetService $service): BudgetResource
    {
        Gate::authorize('viewReports', $household);

        $budget = $service->createBudget($household, $request->validated());

        return new BudgetResource($budget);
    }

    public function show(Household $household, BudgetService $service): JsonResponse
    {
        Gate::authorize('viewReports', $household);

        return response()->json($service->householdSummary($household));
    }
}
