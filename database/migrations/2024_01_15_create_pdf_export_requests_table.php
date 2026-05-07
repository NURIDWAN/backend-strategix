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
        Schema::create('pdf_export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Export Details
            $table->enum('export_type', ['business_plan', 'financial_plan', 'forecast'])->default('business_plan');
            $table->unsignedBigInteger('business_id');
            $table->enum('package', ['professional', 'business'])->default('professional');
            $table->integer('amount')->default(0); // in IDR
            
            // Optional filters
            $table->integer('year')->nullable();
            $table->integer('month')->nullable();
            
            // Payment Status
            $table->enum('status', ['pending_payment', 'completed', 'failed', 'expired'])->default('pending_payment');
            
            // Payment Method
            $table->enum('payment_method', ['virtual_account', 'qris', 'payment_link'])->nullable();
            
            // Virtual Account Details
            $table->string('payment_va_number')->nullable();
            $table->string('payment_va_bank')->nullable(); // BRI, BCA, MANDIRI, BTN
            
            // QRIS & Payment Link
            $table->longText('payment_qris_data')->nullable(); // base64 encoded QR code
            $table->string('payment_link')->nullable();
            
            // Singapay Reference
            $table->string('singapay_reference_id')->nullable();
            
            // Payment Expiry
            $table->timestamp('payment_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // PDF File
            $table->string('pdf_path')->nullable();
            $table->string('pdf_filename')->nullable();
            $table->string('pdf_download_url')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('export_type');
            $table->index('status');
            $table->index('payment_method');
            $table->index('payment_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_export_requests');
    }
};
