<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_type_id')->constrained('account_types');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->string('name');
            $table->bigInteger('opening_balance_minor')->default(0);
            $table->boolean('is_shared')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
