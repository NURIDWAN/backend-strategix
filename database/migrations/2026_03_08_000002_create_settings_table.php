<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();        // e.g., "general", "payment", "affiliate"
            $table->string('key', 100)->unique();         // e.g., "site_name", "maintenance_mode"
            $table->text('value')->nullable();             // stored as string, cast by type
            $table->string('type', 20)->default('string'); // string, boolean, number, json
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
