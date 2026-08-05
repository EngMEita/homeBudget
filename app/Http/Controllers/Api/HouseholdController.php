<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Resources\HouseholdDashboardResource;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Enums\HouseholdRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HouseholdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $households = $request->user()?->households()
            ->orderBy('households.id')
            ->get(['households.id', 'households.uuid', 'households.name', 'households.base_currency_code', 'households.default_locale', 'households.owner_user_id']);

        return response()->json([
            'data' => HouseholdDashboardResource::collection($households),
        ]);
    }

    public function store(StoreHouseholdRequest $request): JsonResponse
    {
        $user = $request->user();
        $household = Household::create([
            'name' => $request->string('name')->toString(),
            'base_currency_code' => $request->string('base_currency_code')->toString(),
            'default_locale' => $request->string('default_locale')->toString() ?: 'en',
            'owner_user_id' => $user->id,
            'is_active' => true,
        ]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        return response()->json([
            'data' => new HouseholdDashboardResource($household),
        ], 201);
    }
}
