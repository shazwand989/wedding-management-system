<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->boolean('invitation_sent')->default(false);
            $table->dateTime('invitation_sent_at')->nullable();
            $table->boolean('viewed_invitation')->default(false);
            $table->dateTime('viewed_at')->nullable();
            $table->string('group_name')->nullable(); // For organizing guests (Family, Friends, etc.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guests');
    }
};
