<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'base_currency_code' => $this->base_currency_code,
            'default_locale' => $this->default_locale,
            'owner_user_id' => $this->owner_user_id,
            'counts' => [
                'accounts' => $this->accounts_count ?? 0,
                'transactions' => $this->transactions_count ?? 0,
                'recurring_rules' => $this->recurring_rules_count ?? 0,
                'upcoming_bills' => $this->upcoming_bills_count ?? 0,
                'savings_goals' => $this->savings_goals_count ?? 0,
                'debts' => $this->debts_count ?? 0,
            ],
            'upcoming_bills' => $this->whenLoaded('latestUpcomingBills', fn () => UpcomingBillResource::collection($this->latestUpcomingBills)->resolve(), []),
            'audit_logs' => $this->whenLoaded('latestAuditLogs', fn () => AuditLogResource::collection($this->latestAuditLogs)->resolve(), []),
        ];
    }
}
