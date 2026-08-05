<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Debt;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\RecurringRule;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\UpcomingBill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HouseholdDemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@example.com'],
            ['name' => 'Household Owner', 'password' => bcrypt('password')]
        );

        $household = Household::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Family Budget',
            'base_currency_code' => 'SAR',
            'default_locale' => 'en',
            'owner_user_id' => $owner->id,
            'is_active' => true,
        ]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $currency = Currency::firstOrCreate(['code' => 'SAR'], [
            'name_en' => 'Saudi Riyal',
            'name_ar' => 'ريال سعودي',
            'symbol' => 'SAR',
            'decimal_places' => 2,
            'minor_unit_factor' => 100,
            'is_active' => true,
        ]);

        $accountType = AccountType::create([
            'household_id' => $household->id,
            'name' => 'Cash',
            'code' => 'cash',
            'is_system' => true,
        ]);

        $account = Account::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Main Wallet',
            'opening_balance_minor' => 250000,
            'is_shared' => true,
            'is_active' => true,
        ]);

        $category = Category::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'name' => 'Groceries',
            'type' => 'expense',
            'is_active' => true,
        ]);

        Transaction::create([
            'uuid' => (string) Str::uuid(),
            'client_uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Family groceries',
            'amount_minor' => 12345,
            'base_amount_minor' => 12345,
            'exchange_rate' => null,
            'exchange_rate_source' => null,
            'transaction_date' => now()->toDateString(),
            'metadata' => ['source' => 'seed'],
            'version' => 1,
        ]);

        $rentRule = RecurringRule::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'created_by' => $owner->id,
            'name' => 'Monthly rent',
            'type' => 'expense',
            'frequency' => 'monthly',
            'amount_minor' => 350000,
            'base_amount_minor' => 350000,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'next_run_on' => now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            'auto_post' => false,
            'is_active' => true,
            'metadata' => ['label_ar' => 'الإيجار الشهري'],
        ]);

        UpcomingBill::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'recurring_rule_id' => $rentRule->id,
            'created_by' => $owner->id,
            'name' => 'Monthly rent',
            'amount_minor' => 350000,
            'base_amount_minor' => 350000,
            'due_on' => now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            'status' => 'scheduled',
            'reminder_status' => 'pending',
        ]);

        SavingsGoal::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'currency_id' => $currency->id,
            'created_by' => $owner->id,
            'name' => 'Emergency fund',
            'target_minor_amount' => 1000000,
            'current_minor_amount' => 150000,
            'target_date' => now()->addMonths(10)->toDateString(),
            'status' => 'active',
        ]);

        Debt::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'currency_id' => $currency->id,
            'created_by' => $owner->id,
            'name' => 'Family loan',
            'counterparty_name' => 'Relative',
            'direction' => 'owed_by_household',
            'principal_minor_amount' => 500000,
            'remaining_minor_amount' => 420000,
            'status' => 'active',
            'opened_on' => now()->subMonths(2)->toDateString(),
            'due_on' => now()->addMonths(8)->toDateString(),
        ]);

        AuditLog::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'event' => 'demo.seeded',
            'metadata' => ['source' => 'HouseholdDemoSeeder'],
        ]);

        BackupLog::create([
            'uuid' => (string) Str::uuid(),
            'household_id' => $household->id,
            'created_by' => $owner->id,
            'type' => 'manual',
            'status' => 'completed',
            'health_check' => ['ok' => true, 'integrity_check' => 'ok', 'foreign_key_violations' => 0],
            'completed_at' => now(),
        ]);
    }
}
