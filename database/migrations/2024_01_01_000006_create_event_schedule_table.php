<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->foreignId('assigned_vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedule');
    }
};
