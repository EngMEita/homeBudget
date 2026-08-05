<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Household;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class AccountDeleteController extends Controller
{
    public function __invoke(Household $household, Account $account): JsonResponse
    {
        Gate::authorize('deleteAccount', $household);
        abort_unless($account->household_id === $household->getKey(), 404);

        $account->delete();

        return response()->json(['deleted' => true]);
    }
}
