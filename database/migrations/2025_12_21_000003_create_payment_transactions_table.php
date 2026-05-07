<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_purchase_id')->constrained()->onDelete('cascade');
            $table->string('transaction_code')->unique();
            $table->string('reference_no')->unique();
            $table->string('payment_method'); // 'virtual_account', 'qris', 'payment_link'
            $table->string('bank_code')->nullable(); // For VA
            $table->string('va_number')->nullable(); // Virtual Account number
            $table->text('qris_content')->nullable(); // QRIS base64 image
            $table->string('qris_url')->nullable(); // QRIS URL
            $table->text('payment_url')->nullable(); // Payment Link URL
            $table->integer('amount');
            $table->string('currency')->default('IDR');
            $table->string('status'); // 'pending', 'paid', 'expired', 'failed'
            $table->string('mode'); // 'mock', 'sandbox', 'production'
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('singapay_request')->nullable(); // Request to SingaPay API
            $table->json('singapay_response')->nullable(); // Response from SingaPay API
            $table->json('webhook_data')->nullable(); // Webhook callback data
            $table->timestamps();

            $table->index('transaction_code');
            $table->index('reference_no');
            $table->index('status');
            $table->index('payment_method');
            $table->index(['pdf_purchase_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
