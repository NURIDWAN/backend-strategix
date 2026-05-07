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
        Schema::create('singapay_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_request_id')->constrained('pdf_export_requests')->onDelete('cascade');
            
            // Reference IDs
            $table->string('reference_id')->unique(); // Unique reference from export request
            $table->string('singapay_transaction_id')->unique()->nullable(); // Transaction ID from Singapay
            
            // Payment Amount
            $table->integer('amount')->default(0); // in IDR
            
            // Payment Method Details
            $table->enum('payment_method', ['virtual_account', 'qris', 'payment_link'])->default('virtual_account');
            $table->string('bank_code')->nullable(); // BRI, BCA, MANDIRI, BTN
            $table->string('va_number')->nullable();
            
            // Payment Status
            $table->enum('status', ['pending', 'settled', 'failed', 'cancelled'])->default('pending');
            
            // Response Data from Singapay
            $table->json('response_data')->nullable(); // Store full API response
            $table->json('webhook_data')->nullable(); // Store webhook payload
            
            // Payment Confirmation
            $table->timestamp('paid_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('export_request_id');
            $table->index('reference_id');
            $table->index('singapay_transaction_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('singapay_payments');
    }
};
