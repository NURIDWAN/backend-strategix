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
        Schema::create('product_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->nullable()->constrained('business_backgrounds')->onDelete('cascade');

            // Core fields sesuai revisi
            $table->enum('type', ['product', 'service'])->default('product');
            $table->string('name'); // Product/Service Name
            $table->text('description'); // Product/Service Description
            $table->decimal('price', 12, 2)->nullable(); // Selling Price (Optional)
            $table->string('image_path')->nullable(); // Product Image Upload (Optional)
            $table->text('advantages')->nullable(); // Product Advantages
            $table->text('development_strategy')->nullable(); // Product Development Strategy

            // BMC Alignment fields
            $table->json('bmc_alignment')->nullable(); // Business Model Canvas Alignment

            $table->enum('status', ['draft', 'in_development', 'launched'])->default('draft');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'business_background_id']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_services');
    }
};
