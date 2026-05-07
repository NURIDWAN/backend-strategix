<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premium_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('package_type'); // 'monthly' or 'yearly'
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price'); // in cents/smallest unit
            $table->integer('duration_days');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('package_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_pdfs');
    }
};
