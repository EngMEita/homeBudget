<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('client_uuid');
            $table->string('operation_type', 80);
            $table->string('status', 40)->default('pending');
            $table->json('payload');
            $table->json('result')->nullable();
            $table->text('conflict_reason')->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'client_uuid', 'operation_type']);
            $table->index(['household_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
