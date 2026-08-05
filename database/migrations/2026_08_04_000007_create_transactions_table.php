<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('counterpart_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->bigInteger('amount_minor');
            $table->bigInteger('base_amount_minor');
            $table->bigInteger('transfer_fee_minor')->default(0);
            $table->string('exchange_rate', 64)->nullable();
            $table->string('exchange_rate_source')->nullable();
            $table->date('exchange_rate_date')->nullable();
            $table->date('transaction_date');
            $table->json('metadata')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'transaction_date']);
            $table->index(['household_id', 'status']);
            $table->index(['account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
