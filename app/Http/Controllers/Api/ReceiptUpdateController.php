<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Household;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\Gate;

class ReceiptUpdateController extends Controller
{
    public function __invoke(UpdateReceiptRequest $request, Household $household, Receipt $receipt, ReceiptService $service): ReceiptResource
    {
        Gate::authorize('updateTransaction', $household);
        abort_unless($receipt->household_id === $household->getKey(), 404);

        $receipt = $service->update($receipt, $request->validated());

        return new ReceiptResource($receipt->load(['allocations', 'attachments']));
    }
}
