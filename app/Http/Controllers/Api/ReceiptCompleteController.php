<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptResource;
use App\Models\Household;
use App\Models\Receipt;
use App\Services\ReceiptCompletionService;
use Illuminate\Support\Facades\Gate;

class ReceiptCompleteController extends Controller
{
    public function __invoke(Household $household, Receipt $receipt, ReceiptCompletionService $service): ReceiptResource
    {
        Gate::authorize('updateTransaction', $household);
        abort_unless($receipt->household_id === $household->getKey(), 404);

        return new ReceiptResource($service->complete($receipt)->load(['allocations', 'attachments']));
    }
}
