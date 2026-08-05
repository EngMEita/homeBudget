<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Household;
use App\Services\AccountService;
use Illuminate\Support\Facades\Gate;

class AccountUpdateController extends Controller
{
    public function __invoke(UpdateAccountRequest $request, Household $household, Account $account, AccountService $service): AccountResource
    {
        Gate::authorize('updateAccount', $household);
        abort_unless($account->household_id === $household->getKey(), 404);

        $account = $service->update($account, $request->validated());

        return new AccountResource($account);
    }
}
