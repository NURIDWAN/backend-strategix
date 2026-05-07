<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->constrained()->onDelete('cascade');

            // Lokasi Usaha
            $table->string('business_location');
            $table->text('location_description')->nullable();
            $table->string('location_type'); // owned, rented, virtual, etc
            $table->decimal('location_size', 10, 2)->nullable(); // in square meters
            $table->decimal('rent_cost', 15, 2)->nullable();

            // Karyawan
            $table->json('employees')->nullable();

            // Jam Operasional
            $table->json('operational_hours')->nullable();

            // Supplier & Mitra
            $table->json('suppliers')->nullable();

            // Alur Kerja - Text dan Diagram
            $table->text('daily_workflow');
            $table->json('workflow_diagram')->nullable(); // Untuk menyimpan data diagram
            $table->string('workflow_image_path')->nullable(); // Untuk menyimpan gambar diagram

            // Equipment & Technology
            $table->text('equipment_needs')->nullable();
            $table->text('technology_stack')->nullable();

            // Status
            $table->enum('status', ['draft', 'completed'])->default('draft');

            $table->timestamps();

            // Indexes untuk performa
            $table->index(['user_id', 'business_background_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_plans');
    }
};
