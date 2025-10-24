<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->enum('service_type', ['photography', 'catering', 'decoration', 'music', 'venue', 'other']);
            $table->text('description')->nullable();
            $table->string('price_range', 100)->nullable();
            $table->string('location')->nullable();
            $table->text('specialties')->nullable();
            $table->json('portfolio_images')->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
