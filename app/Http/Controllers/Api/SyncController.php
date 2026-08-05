<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncOperationsRequest;
use App\Models\Household;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function store(SyncOperationsRequest $request, Household $household, SyncService $service): JsonResponse
    {
        return response()->json([
            'results' => $service->apply($household, $request->user()->id, $request->validated('operations')),
        ]);
    }
}
