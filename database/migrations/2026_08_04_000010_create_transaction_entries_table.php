<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->bigInteger('amount_minor');
            $table->string('direction', 20);
            $table->string('entry_type', 50);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'account_id']);
            $table->index(['household_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
    }
};
