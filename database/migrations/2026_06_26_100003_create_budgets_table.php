<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('period', ['monthly', 'weekly'])->default('monthly');
            $table->date('start_date');
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'period', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
