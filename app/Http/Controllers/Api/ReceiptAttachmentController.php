<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptAttachmentRequest;
use App\Http\Resources\ReceiptAttachmentResource;
use App\Models\Household;
use App\Models\Receipt;
use App\Models\ReceiptAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReceiptAttachmentController extends Controller
{
    public function store(StoreReceiptAttachmentRequest $request, Household $household, Receipt $receipt): JsonResponse
    {
        Gate::authorize('updateTransaction', $household);
        abort_unless($receipt->household_id === $household->getKey(), 404);

        $file = $request->file('attachment');
        $path = $file->store("receipts/{$receipt->uuid}", 'local');

        $attachment = ReceiptAttachment::create([
            'receipt_id' => $receipt->id,
            'uploaded_by_user_id' => $request->user()->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'data' => new ReceiptAttachmentResource($attachment),
        ], 201);
    }
}
