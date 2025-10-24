<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type')->default('wedding'); // wedding, reception, party, etc.
            $table->dateTime('event_date');
            $table->time('event_time');
            $table->string('venue_name');
            $table->text('venue_address');
            $table->string('venue_google_maps')->nullable();
            $table->string('invitation_code')->unique(); // Unique code for invitation link
            $table->integer('max_guests')->nullable();
            $table->json('custom_fields')->nullable(); // Additional info fields
            $table->text('special_instructions')->nullable();
            $table->string('cover_image')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->boolean('rsvp_enabled')->default(true);
            $table->dateTime('rsvp_deadline')->nullable();
            $table->boolean('allow_plus_one')->default(false);
            $table->boolean('send_reminders')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
