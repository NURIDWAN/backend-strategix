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
        Schema::table('users', function (Blueprint $table) {
            $table->after('phone', function (Blueprint $table) {
                $table->string('business_name')->nullable();
                $table->text('business_description')->nullable();
                $table->string('business_tagline')->nullable();
                $table->string('business_logo')->nullable();
                $table->string('whatsapp')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'business_description',
                'business_tagline',
                'business_logo',
                'whatsapp',
            ]);
        });
    }
};
