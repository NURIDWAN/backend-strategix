<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained('pdf_purchases')->onDelete('cascade');
            $table->decimal('subscription_amount', 15, 2);
            $table->integer('commission_percentage')->default(17);
            $table->decimal('commission_amount', 15, 2);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('approved');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('affiliate_user_id');
            $table->index('referred_user_id');
            $table->index('status');
            $table->index(['affiliate_user_id', 'status']);

            // Unique constraint: one commission per purchase
            $table->unique('purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
