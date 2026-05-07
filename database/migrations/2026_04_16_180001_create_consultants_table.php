<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('specialization')->nullable();
            $table->text('bio')->nullable();
            $table->string('google_calendar_id')->nullable()->comment('Email kalender Google yang di-share ke Service Account');
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->integer('max_daily_sessions')->default(4);
            $table->time('working_hours_start')->default('09:00');
            $table->time('working_hours_end')->default('17:00');
            $table->json('working_days')->nullable()->comment('Array of day numbers: 1=Mon, 7=Sun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultants');
    }
};
