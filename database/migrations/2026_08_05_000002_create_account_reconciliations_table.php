<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->bigInteger('previous_balance_minor');
            $table->bigInteger('statement_balance_minor');
            $table->bigInteger('difference_minor');
            $table->date('reconciled_on');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'reconciled_on']);
            $table->index(['account_id', 'reconciled_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_reconciliations');
    }
};
