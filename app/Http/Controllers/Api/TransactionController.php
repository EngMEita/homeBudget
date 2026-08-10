<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\StoreSplitExpenseRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Household;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request, Household $household, TransactionService $service): TransactionResource
    {
        Gate::authorize('createTransaction', $household);

        $transaction = $service->create($household, array_merge(
            $request->validated(),
            ['created_by' => $request->user()->getKey()]
        ));

        return new TransactionResource($transaction->load(['household']));
    }

    public function storeSplitExpense(StoreSplitExpenseRequest $request, Household $household, TransactionService $service): TransactionResource
    {
        $transaction = $service->createSplitExpense($household, array_merge($request->validated(), [
            'type' => 'expense', 'created_by' => $request->user()->getKey(),
        ]));
        return new TransactionResource($transaction);
    }
}
