<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('period_type')->default('monthly');
            $table->string('base_currency_code', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'is_active']);
        });

        Schema::create('budget_periods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['budget_id', 'starts_on', 'ends_on']);
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('budget_period_id')->constrained('budget_periods')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->bigInteger('planned_minor_amount');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['budget_period_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budget_periods');
        Schema::dropIfExists('budgets');
    }
};
