<?php
// database/migrations/2025_11_09_155228_create_team_structures_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->constrained()->onDelete('cascade');
            $table->foreignId('operational_plan_id')->nullable()->constrained()->onDelete('cascade'); //  Relasi ke operational plan

            $table->string('team_category')->nullable();
            $table->string('member_name');
            $table->string('position');
            $table->text('experience')->nullable();
            $table->string('photo')->nullable(); 
            $table->integer('sort_order')->default(0); //  untuk urutan tampilan

            $table->enum('status', ['draft', 'active'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_structures');
    }
};
