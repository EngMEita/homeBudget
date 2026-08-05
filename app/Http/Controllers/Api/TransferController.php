<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Household;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Gate;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request, Household $household, TransactionService $service): TransactionResource
    {
        Gate::authorize('createTransaction', $household);

        $transaction = $service->create($household, array_merge(
            $request->validated(),
            ['created_by' => $request->user()->getKey()]
        ));

        return new TransactionResource($transaction->load(['household']));
    }
}
