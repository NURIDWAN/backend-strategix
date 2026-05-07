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
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_identifier')->unique()->comment('Unique page key, e.g. home, pricing');
            $table->string('page_name')->comment('Display name, e.g. Beranda');
            $table->string('title')->nullable()->comment('HTML title tag');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable()->comment('Comma-separated keywords');
            $table->string('og_title')->nullable()->comment('Open Graph title');
            $table->text('og_description')->nullable()->comment('Open Graph description');
            $table->string('og_image')->nullable()->comment('Open Graph image URL');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
