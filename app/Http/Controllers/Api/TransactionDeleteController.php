<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\Transaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class TransactionDeleteController extends Controller
{
    public function __invoke(Household $household, Transaction $transaction): JsonResponse
    {
        Gate::authorize('deleteTransaction', $household);
        abort_unless($transaction->household_id === $household->getKey(), 404);

        $transaction->delete();

        return response()->json(['deleted' => true]);
    }
}
