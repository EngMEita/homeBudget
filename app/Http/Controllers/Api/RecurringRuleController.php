<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringRuleRequest;
use App\Http\Resources\RecurringRuleResource;
use App\Models\Household;
use App\Services\RecurringService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RecurringRuleController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('viewTransactions', $household);

        return RecurringRuleResource::collection($household->recurringRules()->latest('id')->paginate(20));
    }

    public function store(StoreRecurringRuleRequest $request, Household $household, RecurringService $service): RecurringRuleResource
    {
        $rule = $service->createRule($household, $request->user()->id, $request->validated());

        return new RecurringRuleResource($rule);
    }
}
