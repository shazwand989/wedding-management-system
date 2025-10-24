<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->enum('category', ['venue', 'photography', 'attire', 'flowers', 'music', 'transportation', 'stationery', 'rings', 'miscellaneous']);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('vendor_name')->nullable();
            $table->date('expense_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_expenses');
    }
};
