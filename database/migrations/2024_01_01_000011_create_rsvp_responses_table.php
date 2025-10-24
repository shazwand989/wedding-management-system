<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvp_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('guest_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone')->nullable();
            $table->enum('response_status', ['yes', 'no', 'maybe'])->default('maybe');
            $table->integer('number_of_guests')->default(1);
            $table->text('message')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->json('custom_responses')->nullable(); // Answers to custom questions
            $table->boolean('checked_in')->default(false);
            $table->dateTime('checked_in_at')->nullable();
            $table->string('qr_code')->nullable(); // QR code for check-in
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvp_responses');
    }
};
