<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SystemHealthController extends Controller
{
    public function __invoke(Household $household, SystemHealthService $health): JsonResponse
    {
        Gate::authorize('export', $household);

        return response()->json(['data' => $health->forHousehold($household)]);
    }
}
