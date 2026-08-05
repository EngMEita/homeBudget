<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalContributionRequest;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Models\Household;
use App\Models\SavingsGoal;
use App\Services\GoalDebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SavingsGoalController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('viewReports', $household);

        return SavingsGoalResource::collection($household->savingsGoals()->latest('id')->paginate(20));
    }

    public function store(StoreSavingsGoalRequest $request, Household $household, GoalDebtService $service): SavingsGoalResource
    {
        $goal = $service->createGoal($household, $request->user()->id, $request->validated());

        return new SavingsGoalResource($goal);
    }

    public function contribute(StoreGoalContributionRequest $request, Household $household, SavingsGoal $goal, GoalDebtService $service): JsonResponse
    {
        Gate::authorize('createTransaction', $household);
        abort_unless($goal->household_id === $household->id, 404);

        $service->contribute($goal, $request->user()->id, $request->validated());

        return response()->json(['data' => new SavingsGoalResource($goal->refresh())]);
    }
}
