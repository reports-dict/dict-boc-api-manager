<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmissions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['discharge', 'load', 'release', 'receive']);
            $table->enum('status', ['pending', 'success', 'partial', 'failed'])->default('pending');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('records_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->enum('triggered_by', ['manual', 'auto'])->default('manual');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('response_summary')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmissions');
    }
};
