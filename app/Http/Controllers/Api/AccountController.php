<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Household;
use App\Services\AccountService;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function store(StoreAccountRequest $request, Household $household, AccountService $service): AccountResource
    {
        Gate::authorize('manage', $household);

        $account = $service->create($household, $request->validated());

        return new AccountResource($account->load(['household']));
    }
}
