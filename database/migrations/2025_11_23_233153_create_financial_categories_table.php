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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->constrained('business_backgrounds')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['income', 'expense']);
            $table->string('color')->default('#6B7280'); // default gray
            $table->enum('status', ['actual', 'plan'])->default('actual');
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint untuk mencegah duplikasi nama kategori per user per business
            $table->unique(['user_id', 'business_background_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
