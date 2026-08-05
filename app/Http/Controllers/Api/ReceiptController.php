<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Household;
use App\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function store(StoreReceiptRequest $request, Household $household, ReceiptService $service): ReceiptResource
    {
        \Gate::authorize('createTransaction', $household);

        $receipt = $service->create(array_merge($request->validated(), [
            'household_id' => $household->id,
            'created_by' => $request->user()->id,
        ]));

        return new ReceiptResource($receipt->load(['allocations', 'attachments']));
    }
}
