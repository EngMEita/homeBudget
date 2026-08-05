<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDebtInstallmentRequest;
use App\Http\Requests\StoreDebtRequest;
use App\Http\Resources\DebtResource;
use App\Models\Debt;
use App\Models\Household;
use App\Services\GoalDebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DebtController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('viewReports', $household);

        return DebtResource::collection($household->debts()->latest('id')->paginate(20));
    }

    public function store(StoreDebtRequest $request, Household $household, GoalDebtService $service): DebtResource
    {
        $debt = $service->createDebt($household, $request->user()->id, $request->validated());

        return new DebtResource($debt);
    }

    public function installment(StoreDebtInstallmentRequest $request, Household $household, Debt $debt, GoalDebtService $service): JsonResponse
    {
        Gate::authorize('createTransaction', $household);
        abort_unless($debt->household_id === $household->id, 404);

        $service->payInstallment($debt, $request->user()->id, $request->validated());

        return response()->json(['data' => new DebtResource($debt->refresh())]);
    }
}
