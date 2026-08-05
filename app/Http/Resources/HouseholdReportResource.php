<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class HouseholdReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'household_id' => $this->id,
            'household_name' => $this->name,
            'base_currency_code' => $this->base_currency_code,
            'total_accounts' => $this->accounts_count ?? 0,
            'total_transactions' => $this->transactions_count ?? 0,
            'total_recurring_rules' => $this->recurring_rules_count ?? 0,
            'total_upcoming_bills' => $this->upcoming_bills_count ?? 0,
            'total_savings_goals' => $this->savings_goals_count ?? 0,
            'total_debts' => $this->debts_count ?? 0,
            'recent_transactions' => $this->whenLoaded('recentTransactions', function (): array {
                return TransactionResource::collection($this->recentTransactions)->resolve();
            }, []),
        ];
    }
}
