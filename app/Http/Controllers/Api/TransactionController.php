<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\StoreSplitExpenseRequest;
use App\Http\Requests\UpdatePaymentLegsRequest;
use App\Http\Requests\StorePartialRefundRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Household;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    public function show(Household $household, Transaction $transaction): TransactionResource
    {
        Gate::authorize('viewTransactions', $household);
        abort_unless($transaction->household_id === $household->id, 404);

        return new TransactionResource($transaction->load(['paymentLegs', 'account', 'currency']));
    }

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

    public function updatePaymentLegs(UpdatePaymentLegsRequest $request, Household $household, Transaction $transaction, TransactionService $service): TransactionResource
    {
        abort_unless($transaction->household_id === $household->id && $transaction->type === 'expense', 404);
        return new TransactionResource($service->updatePaymentLegs($transaction, $request->validated()));
    }

    public function storePartialRefund(StorePartialRefundRequest $request, Household $household, Transaction $transaction, TransactionService $service): TransactionResource
    {
        abort_unless($transaction->household_id === $household->id && $transaction->type === 'expense', 404);
        return new TransactionResource($service->createPartialRefund($transaction, array_merge($request->validated(), ['created_by' => $request->user()->id])));
    }
}
