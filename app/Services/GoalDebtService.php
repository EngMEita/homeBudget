<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\GoalContribution;
use App\Models\Household;
use App\Models\SavingsGoal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoalDebtService
{
    public function createGoal(Household $household, int $userId, array $data): SavingsGoal
    {
        return DB::transaction(function () use ($household, $userId, $data): SavingsGoal {
            $goal = SavingsGoal::create([
                'household_id' => $household->id,
                'currency_id' => $data['currency_id'],
                'created_by' => $userId,
                'name' => $data['name'],
                'target_minor_amount' => (int) $data['target_minor_amount'],
                'current_minor_amount' => 0,
                'target_date' => $data['target_date'] ?? null,
                'status' => 'active',
            ]);

            app(AuditLogService::class)->record($household, $userId, 'savings_goal.created', $goal);

            return $goal;
        });
    }

    public function contribute(SavingsGoal $goal, int $userId, array $data): GoalContribution
    {
        return DB::transaction(function () use ($goal, $userId, $data): GoalContribution {
            $amount = (int) $data['amount_minor'];
            $contribution = GoalContribution::create([
                'savings_goal_id' => $goal->id,
                'transaction_id' => $data['transaction_id'] ?? null,
                'created_by' => $userId,
                'amount_minor' => $amount,
                'contributed_on' => $data['contributed_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            $goal->increment('current_minor_amount', $amount);
            $goal->refresh();
            if ($goal->current_minor_amount >= $goal->target_minor_amount) {
                $goal->forceFill(['status' => 'completed'])->save();
            }

            app(AuditLogService::class)->record($goal->household, $userId, 'savings_goal.contributed', $goal, ['amount_minor' => $amount]);

            return $contribution;
        });
    }

    public function createDebt(Household $household, int $userId, array $data): Debt
    {
        return DB::transaction(function () use ($household, $userId, $data): Debt {
            $debt = Debt::create([
                'household_id' => $household->id,
                'currency_id' => $data['currency_id'],
                'created_by' => $userId,
                'name' => $data['name'],
                'counterparty_name' => $data['counterparty_name'],
                'direction' => $data['direction'] ?? 'owed_by_household',
                'principal_minor_amount' => (int) $data['principal_minor_amount'],
                'remaining_minor_amount' => (int) $data['principal_minor_amount'],
                'status' => 'active',
                'opened_on' => $data['opened_on'],
                'due_on' => $data['due_on'] ?? null,
            ]);

            app(AuditLogService::class)->record($household, $userId, 'debt.created', $debt);

            return $debt;
        });
    }

    public function payInstallment(Debt $debt, int $userId, array $data): DebtInstallment
    {
        return DB::transaction(function () use ($debt, $userId, $data): DebtInstallment {
            $principal = (int) $data['principal_minor_amount'];
            if ($principal > $debt->remaining_minor_amount) {
                throw ValidationException::withMessages([
                    'principal_minor_amount' => 'Installment principal cannot exceed the remaining debt amount.',
                ]);
            }

            $installment = DebtInstallment::create([
                'debt_id' => $debt->id,
                'transaction_id' => $data['transaction_id'] ?? null,
                'created_by' => $userId,
                'principal_minor_amount' => $principal,
                'interest_minor_amount' => (int) ($data['interest_minor_amount'] ?? 0),
                'paid_on' => $data['paid_on'],
            ]);

            $debt->decrement('remaining_minor_amount', $principal);
            $debt->refresh();
            if ($debt->remaining_minor_amount === 0) {
                $debt->forceFill(['status' => 'settled'])->save();
            }

            app(AuditLogService::class)->record($debt->household, $userId, 'debt.installment_paid', $debt, [
                'principal_minor_amount' => $principal,
                'interest_minor_amount' => (int) ($data['interest_minor_amount'] ?? 0),
            ]);

            return $installment;
        });
    }
}
