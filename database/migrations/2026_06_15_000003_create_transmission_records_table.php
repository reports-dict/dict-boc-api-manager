<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmission_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transmission_id')->constrained()->cascadeOnDelete();
            $table->string('container_no', 20)->index();
            $table->json('payload');
            $table->enum('status', ['success', 'failed', 'duplicate'])->default('success');
            $table->text('response_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmission_records');
    }
};
