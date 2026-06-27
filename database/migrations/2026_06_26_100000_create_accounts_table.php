<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['cash', 'bank', 'credit_card', 'debit_card', 'digital_wallet', 'other'])->default('bank');
            $table->char('currency', 3)->default('USD');
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
