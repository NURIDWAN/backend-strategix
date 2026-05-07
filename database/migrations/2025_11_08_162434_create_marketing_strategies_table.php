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
        Schema::create('marketing_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->nullable()->constrained('business_backgrounds')->onDelete('cascade');

            $table->text('promotion_strategy');
            $table->text('media_used')->nullable();
            $table->text('pricing_strategy')->nullable();
            $table->integer('monthly_target')->nullable();
            $table->text('collaboration_plan')->nullable();
            $table->enum('status', ['draft', 'active'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_strategies');
    }
};
