<?php
// database/migrations/2025_11_24_000000_create_financial_simulations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->constrained('business_backgrounds')->onDelete('cascade');
            $table->foreignId('financial_category_id')->constrained()->onDelete('cascade');
            $table->string('simulation_code')->unique();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->date('simulation_date');
            $table->string('description')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'digital_wallet', 'other'])->default('cash');
            $table->enum('status', ['planned', 'completed', 'cancelled'])->default('planned');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Index untuk performa query
            $table->index(['user_id', 'simulation_date']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'financial_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_simulations');
    }
};
