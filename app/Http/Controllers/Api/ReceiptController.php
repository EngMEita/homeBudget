<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Household;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    public function index(Household $household)
    {
        Gate::authorize('viewTransactions', $household);

        return ReceiptResource::collection($household->receipts()->with(['allocations', 'attachments'])->latest()->paginate(20));
    }

    public function show(Household $household, Receipt $receipt): ReceiptResource
    {
        Gate::authorize('viewTransactions', $household);
        abort_unless($receipt->household_id === $household->id, 404);

        return new ReceiptResource($receipt->load(['allocations', 'attachments']));
    }

    public function store(StoreReceiptRequest $request, Household $household, ReceiptService $service): ReceiptResource
    {
        \Gate::authorize('createTransaction', $household);

        $receipt = $service->create(array_merge($request->validated(), [
            'household_id' => $household->id,
            'created_by' => $request->user()->id,
        ]));

        return new ReceiptResource($receipt->load(['allocations', 'attachments']));
    }

    public function destroy(Household $household, Receipt $receipt)
    {
        Gate::authorize('deleteTransaction', $household);
        abort_unless($receipt->household_id === $household->id, 404);
        $receipt->delete();

        return response()->noContent();
    }
}
