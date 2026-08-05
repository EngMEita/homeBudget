<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\BudgetPeriod;
use App\Models\Household;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function createBudget(Household $household, array $data): Budget
    {
        return DB::transaction(function () use ($household, $data): Budget {
            $budget = Budget::create([
                'household_id' => $household->id,
                'name' => $data['name'],
                'period_type' => $data['period_type'] ?? 'monthly',
                'base_currency_code' => $data['base_currency_code'] ?? $household->base_currency_code,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $period = BudgetPeriod::create([
                'budget_id' => $budget->id,
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => $data['status'] ?? 'open',
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                BudgetLine::create([
                    'budget_period_id' => $period->id,
                    'category_id' => $line['category_id'],
                    'planned_minor_amount' => (int) $line['planned_minor_amount'],
                    'is_active' => $line['is_active'] ?? true,
                ]);
            }

            return $budget->load(['periods.lines.category']);
        });
    }

    public function householdSummary(Household $household): array
    {
        $budget = $household->budgets()->with(['periods.lines.category'])->latest('id')->first();
        if (! $budget) {
            return ['budget' => null, 'periods' => []];
        }

        $periods = $budget->periods->map(function (BudgetPeriod $period) use ($household): array {
            $actuals = Transaction::query()
                ->where('household_id', $household->id)
                ->where('status', 'confirmed')
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])
                ->selectRaw('category_id, sum(amount_minor) as actual_minor')
                ->groupBy('category_id')
                ->pluck('actual_minor', 'category_id');

            return [
                'id' => $period->id,
                'starts_on' => $period->starts_on?->toDateString(),
                'ends_on' => $period->ends_on?->toDateString(),
                'status' => $period->status,
                'lines' => $period->lines->map(function (BudgetLine $line) use ($actuals): array {
                    $actual = (int) ($actuals[$line->category_id] ?? 0);
                    return [
                        'category_id' => $line->category_id,
                        'category_name' => $line->category?->name,
                        'planned_minor_amount' => $line->planned_minor_amount,
                        'actual_minor_amount' => $actual,
                        'remaining_minor_amount' => (int) $line->planned_minor_amount - $actual,
                    ];
                })->values(),
            ];
        })->values();

        return [
            'budget' => [
                'id' => $budget->id,
                'name' => $budget->name,
                'period_type' => $budget->period_type,
                'base_currency_code' => $budget->base_currency_code,
            ],
            'periods' => $periods,
        ];
    }
}
