<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('recurring_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->string('type');
            $table->string('frequency')->default('monthly');
            $table->bigInteger('amount_minor');
            $table->bigInteger('base_amount_minor');
            $table->date('starts_on');
            $table->date('next_run_on');
            $table->date('ends_on')->nullable();
            $table->boolean('auto_post')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'next_run_on']);
            $table->index(['household_id', 'is_active']);
        });

        Schema::create('upcoming_bills', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('recurring_rule_id')->nullable()->constrained('recurring_rules')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->bigInteger('amount_minor');
            $table->bigInteger('base_amount_minor');
            $table->date('due_on');
            $table->string('status')->default('scheduled');
            $table->string('reminder_status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'due_on']);
            $table->index(['household_id', 'status']);
        });

        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->bigInteger('target_minor_amount');
            $table->bigInteger('current_minor_amount')->default(0);
            $table->date('target_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status']);
        });

        Schema::create('goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('savings_goal_id')->constrained('savings_goals')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->bigInteger('amount_minor');
            $table->date('contributed_on');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->string('counterparty_name');
            $table->string('direction')->default('owed_by_household');
            $table->bigInteger('principal_minor_amount');
            $table->bigInteger('remaining_minor_amount');
            $table->string('status')->default('active');
            $table->date('opened_on');
            $table->date('due_on')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status']);
        });

        Schema::create('debt_installments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->bigInteger('principal_minor_amount');
            $table->bigInteger('interest_minor_amount')->default(0);
            $table->date('paid_on');
            $table->timestamps();
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('manual');
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('pending');
            $table->json('health_check')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('debt_installments');
        Schema::dropIfExists('debts');
        Schema::dropIfExists('goal_contributions');
        Schema::dropIfExists('savings_goals');
        Schema::dropIfExists('upcoming_bills');
        Schema::dropIfExists('recurring_rules');
        Schema::dropIfExists('audit_logs');
    }
};
