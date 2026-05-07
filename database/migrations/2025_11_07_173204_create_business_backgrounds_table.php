<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Relasi ke user
            $table->string('logo')->nullable(); // Upload logo opsional
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->text('purpose');
            $table->string('location');
            $table->string('business_type'); // CV, PT, UMKM, dll
            $table->date('start_date')->nullable();
            $table->text('values')->nullable(); // mission statement
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->string('contact')->nullable(); // media sosial / kontak
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_backgrounds');
    }
};
