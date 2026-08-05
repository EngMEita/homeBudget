<?php

namespace App\Services;

use App\Models\Household;
use App\Models\RecurringRule;
use App\Models\UpcomingBill;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RecurringService
{
    public function createRule(Household $household, int $userId, array $data): RecurringRule
    {
        return DB::transaction(function () use ($household, $userId, $data): RecurringRule {
            $rule = RecurringRule::create([
                'household_id' => $household->id,
                'account_id' => $data['account_id'],
                'currency_id' => $data['currency_id'],
                'category_id' => $data['category_id'] ?? null,
                'created_by' => $userId,
                'name' => $data['name'],
                'type' => $data['type'],
                'frequency' => $data['frequency'] ?? 'monthly',
                'amount_minor' => (int) $data['amount_minor'],
                'base_amount_minor' => (int) ($data['base_amount_minor'] ?? $data['amount_minor']),
                'starts_on' => $data['starts_on'],
                'next_run_on' => $data['next_run_on'] ?? $data['starts_on'],
                'ends_on' => $data['ends_on'] ?? null,
                'auto_post' => $data['auto_post'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->createBillFromRule($rule, $userId);
            app(AuditLogService::class)->record($household, $userId, 'recurring_rule.created', $rule);

            return $rule;
        });
    }

    public function createBill(Household $household, int $userId, array $data): UpcomingBill
    {
        return DB::transaction(function () use ($household, $userId, $data): UpcomingBill {
            $bill = UpcomingBill::create([
                'household_id' => $household->id,
                'account_id' => $data['account_id'] ?? null,
                'currency_id' => $data['currency_id'],
                'recurring_rule_id' => $data['recurring_rule_id'] ?? null,
                'created_by' => $userId,
                'name' => $data['name'],
                'amount_minor' => (int) $data['amount_minor'],
                'base_amount_minor' => (int) ($data['base_amount_minor'] ?? $data['amount_minor']),
                'due_on' => $data['due_on'],
                'status' => $data['status'] ?? 'scheduled',
                'reminder_status' => $data['reminder_status'] ?? 'pending',
            ]);

            app(AuditLogService::class)->record($household, $userId, 'upcoming_bill.created', $bill);

            return $bill;
        });
    }

    public function dueSummary(Household $household, int $days = 30): array
    {
        $until = now()->addDays($days)->toDateString();

        return [
            'recurring_rules' => $household->recurringRules()
                ->where('is_active', true)
                ->whereDate('next_run_on', '<=', $until)
                ->orderBy('next_run_on')
                ->get(),
            'upcoming_bills' => $household->upcomingBills()
                ->whereIn('status', ['scheduled', 'overdue'])
                ->whereDate('due_on', '<=', $until)
                ->orderBy('due_on')
                ->get(),
        ];
    }

    private function createBillFromRule(RecurringRule $rule, int $userId): UpcomingBill
    {
        return UpcomingBill::create([
            'household_id' => $rule->household_id,
            'account_id' => $rule->account_id,
            'currency_id' => $rule->currency_id,
            'recurring_rule_id' => $rule->id,
            'created_by' => $userId,
            'name' => $rule->name,
            'amount_minor' => $rule->amount_minor,
            'base_amount_minor' => $rule->base_amount_minor,
            'due_on' => CarbonImmutable::parse($rule->next_run_on)->toDateString(),
            'status' => 'scheduled',
            'reminder_status' => 'pending',
        ]);
    }
}
