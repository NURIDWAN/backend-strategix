<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('consultant_id')->constrained('consultants')->onDelete('cascade');
            $table->string('google_event_id')->nullable();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->string('meeting_link')->nullable();
            $table->string('topic')->nullable();
            $table->text('notes_member')->nullable();
            $table->text('notes_consultant')->nullable();
            $table->string('report_type')->nullable()->comment('business_plan, financial, forecast');
            $table->unsignedBigInteger('related_report_id')->nullable();
            $table->tinyInteger('rating')->nullable()->comment('1-5');
            $table->text('review')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['session_date', 'status']);
            $table->index(['consultant_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_sessions');
    }
};
