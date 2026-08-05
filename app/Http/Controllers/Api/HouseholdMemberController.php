<?php

namespace App\Http\Controllers\Api;

use App\Enums\HouseholdRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHouseholdInvitationRequest;
use App\Http\Resources\HouseholdInvitationResource;
use App\Http\Resources\HouseholdMemberResource;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\HouseholdUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class HouseholdMemberController extends Controller
{
    public function index(Household $household): JsonResponse
    {
        Gate::authorize('manage', $household);

        return response()->json([
            'members' => HouseholdMemberResource::collection(
                $household->members()->orderBy('users.name')->get()
            ),
            'invitations' => HouseholdInvitationResource::collection(
                $household->invitations()->latest('id')->get()
            ),
        ]);
    }

    public function invite(StoreHouseholdInvitationRequest $request, Household $household): JsonResponse
    {
        Gate::authorize('manage', $household);

        $invitation = HouseholdInvitation::create([
            'household_id' => $household->id,
            'invited_by_user_id' => $request->user()->id,
            'email' => $request->string('email')->toString(),
            'role' => $request->string('role')->toString(),
            'expires_at' => now()->addDays(14),
        ]);

        return response()->json([
            'data' => new HouseholdInvitationResource($invitation),
        ], 201);
    }

    public function accept(string $token): JsonResponse
    {
        $invitation = HouseholdInvitation::query()->where('token', $token)->firstOrFail();
        abort_if($invitation->accepted_at || $invitation->revoked_at, 409);
        abort_if($invitation->expires_at && $invitation->expires_at->isPast(), 410);

        $user = request()->user();
        abort_unless($user && strcasecmp($user->email, $invitation->email) === 0, 403);

        HouseholdUser::updateOrCreate(
            [
                'household_id' => $invitation->household_id,
                'user_id' => $user->id,
            ],
            [
                'role' => $invitation->role,
                'can_view_balances' => true,
                'can_create_transactions' => in_array($invitation->role, [HouseholdRole::Owner->value, HouseholdRole::Administrator->value, HouseholdRole::Contributor->value, HouseholdRole::Restricted->value], true),
                'can_view_transactions' => true,
            ]
        );

        $invitation->forceFill([
            'invited_user_id' => $user->id,
            'accepted_at' => now(),
        ])->save();

        return response()->json(['accepted' => true]);
    }
}
