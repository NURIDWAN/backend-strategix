<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('premium_pdf_id')->constrained()->onDelete('cascade');
            $table->string('transaction_code')->unique();
            $table->string('package_type'); // 'monthly' or 'yearly'
            $table->integer('amount_paid');
            $table->string('payment_method'); // 'va_bri', 'va_bni', 'qris', etc
            $table->string('status'); // 'pending', 'paid', 'expired', 'failed'
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable(); // Store additional payment info
            $table->timestamps();

            $table->index('user_id');
            $table->index('transaction_code');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_purchases');
    }
};
