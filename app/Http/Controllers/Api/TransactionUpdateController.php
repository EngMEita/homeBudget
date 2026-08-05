<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Household;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Gate;

class TransactionUpdateController extends Controller
{
    public function __invoke(UpdateTransactionRequest $request, Household $household, Transaction $transaction, TransactionService $service): TransactionResource
    {
        Gate::authorize('updateTransaction', $household);
        abort_unless($transaction->household_id === $household->getKey(), 404);

        $transaction = $service->update($transaction, array_merge(
            $request->validated(),
            ['updated_by' => $request->user()->getKey()]
        ));

        return new TransactionResource($transaction);
    }
}
