<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('page_url');
            $table->string('page_title')->nullable();
            $table->string('referrer')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_analytics');
    }
};
