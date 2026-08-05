<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->pivot?->role,
            'can_view_balances' => (bool) $this->pivot?->can_view_balances,
            'can_create_transactions' => (bool) $this->pivot?->can_create_transactions,
            'can_view_transactions' => (bool) $this->pivot?->can_view_transactions,
        ];
    }
}
