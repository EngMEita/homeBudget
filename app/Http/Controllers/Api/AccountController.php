<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\AccountType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Household;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function show(Household $household, Account $account): AccountResource
    {
        Gate::authorize('view', $household);
        abort_unless($account->household_id === $household->id, 404);

        return new AccountResource($account->load(['accountType:id,name', 'currency:id,code,name_en,name_ar']));
    }

    public function index(Household $household): JsonResponse
    {
        Gate::authorize('view', $household);

        return response()->json([
            'data' => AccountResource::collection(
                $household->accounts()
                    ->with(['accountType:id,name', 'currency:id,code,name_en,name_ar'])
                    ->orderBy('name')
                    ->get()
            ),
            'account_types' => AccountType::query()->orderBy('name')->get(['id', 'name']),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name_en', 'name_ar']),
        ]);
    }

    public function store(StoreAccountRequest $request, Household $household, AccountService $service): AccountResource
    {
        Gate::authorize('manage', $household);

        $account = $service->create($household, $request->validated());

        return new AccountResource($account->load(['household']));
    }
}
