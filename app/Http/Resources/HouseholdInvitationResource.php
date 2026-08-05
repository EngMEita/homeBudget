<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'email' => $this->email,
            'role' => $this->role,
            'token' => $this->token,
            'expires_at' => $this->expires_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'invited_by_user_id' => $this->invited_by_user_id,
        ];
    }
}
