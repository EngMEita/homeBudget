<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'household_id' => $this->household_id,
            'name' => $this->name,
            'period_type' => $this->period_type,
            'base_currency_code' => $this->base_currency_code,
            'is_active' => $this->is_active,
            'periods' => $this->whenLoaded('periods', function (): array {
                return $this->periods->map(function ($period): array {
                    return [
                        'id' => $period->id,
                        'starts_on' => $period->starts_on?->toDateString(),
                        'ends_on' => $period->ends_on?->toDateString(),
                        'status' => $period->status,
                        'lines' => $period->lines->map(function ($line): array {
                            return [
                                'id' => $line->id,
                                'category_id' => $line->category_id,
                                'category_name' => $line->category?->name,
                                'planned_minor_amount' => $line->planned_minor_amount,
                                'is_active' => $line->is_active,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all();
            }, []),
        ];
    }
}
