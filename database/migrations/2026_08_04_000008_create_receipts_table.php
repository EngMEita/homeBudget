<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('merchant_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('paid_by_user_id')->constrained('users');
            $table->bigInteger('total_minor_amount');
            $table->bigInteger('base_currency_minor_amount');
            $table->string('exchange_rate', 64)->nullable();
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable();
            $table->string('receipt_status')->default('open');
            $table->string('categorization_status')->default('uncategorized');
            $table->string('receipt_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'transaction_date']);
            $table->index(['household_id', 'categorization_status']);
            $table->index(['account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
