<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Validation\ValidationException;

class ReceiptCompletionService
{
    public function complete(Receipt $receipt): Receipt
    {
        $categorized = app(ReceiptService::class)->categorizedTotal($receipt);

        if ($categorized > (int) $receipt->total_minor_amount) {
            throw ValidationException::withMessages([
                'allocations' => ['Allocation total cannot exceed the receipt total.'],
            ]);
        }

        $receipt->forceFill([
            'categorization_status' => $categorized === (int) $receipt->total_minor_amount ? 'fully_categorized' : 'partially_categorized',
            'version' => $receipt->version + 1,
        ])->save();

        return $receipt;
    }
}
