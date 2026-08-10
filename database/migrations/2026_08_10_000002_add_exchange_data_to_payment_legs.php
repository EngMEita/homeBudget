<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_legs', function (Blueprint $table): void {
            $table->string('exchange_rate', 64)->nullable()->after('base_amount_minor');
            $table->string('exchange_rate_source')->nullable();
            $table->date('exchange_rate_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_legs', function (Blueprint $table): void {
            $table->dropColumn(['exchange_rate', 'exchange_rate_source', 'exchange_rate_date']);
        });
    }
};
