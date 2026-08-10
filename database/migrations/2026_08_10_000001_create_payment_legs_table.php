<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_legs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->bigInteger('amount_minor');
            $table->bigInteger('base_amount_minor');
            $table->timestamps();
            $table->index(['household_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_legs');
    }
};
