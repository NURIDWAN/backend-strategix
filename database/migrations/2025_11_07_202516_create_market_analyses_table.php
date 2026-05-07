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
        Schema::create('market_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // pemilik data
            $table->unsignedBigInteger('business_background_id')->nullable(); // relasi ke usaha (opsional)

            // Fields sesuai checklist lama
            $table->text('target_market')->nullable();
            $table->string('market_size')->nullable();
            $table->text('market_trends')->nullable();
            $table->text('main_competitors')->nullable();
            $table->text('competitor_strengths')->nullable();
            $table->text('competitor_weaknesses')->nullable();
            $table->text('competitive_advantage')->nullable();

            // 🔥 REVISI: Tambahan field baru
            // TAM, SAM, SOM
            $table->decimal('tam_total', 15, 2)->nullable(); // Total Addressable Market
            $table->decimal('sam_percentage', 5, 2)->nullable(); // Serviceable Available Market percentage
            $table->decimal('sam_total', 15, 2)->nullable(); // Serviceable Available Market total
            $table->decimal('som_percentage', 5, 2)->nullable(); // Serviceable Obtainable Market percentage
            $table->decimal('som_total', 15, 2)->nullable(); // Serviceable Obtainable Market total

            // SWOT Analysis
            $table->text('strengths')->nullable(); // Kekuatan (Strengths)
            $table->text('weaknesses')->nullable(); // Kelemahan (Weaknesses)
            $table->text('opportunities')->nullable(); // Peluang (Opportunities)
            $table->text('threats')->nullable(); // Ancaman (Threats)

            $table->timestamps();

            // foreign keys (pastikan tabel users dan business_backgrounds ada)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_background_id')->references('id')->on('business_backgrounds')->onDelete('cascade');
        });

        // 🔥 REVISI: Tabel baru untuk daftar kompetitor detail
        Schema::create('market_analysis_competitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('market_analysis_id');
            $table->string('competitor_name'); // nama kompetitor
            $table->enum('type', ['ownshop', 'competitor'])->default('competitor'); // jenis: ownshop atau kompetitor
            $table->string('code')->nullable(); // kode kompetitor
            $table->text('address')->nullable(); // alamat kompetitor
            $table->decimal('annual_sales_estimate', 15, 2)->nullable(); // estimasi penjualan pertahun
            $table->decimal('selling_price', 15, 2)->nullable(); // harga jual
            $table->text('strengths')->nullable(); // kelebihan
            $table->text('weaknesses')->nullable(); // kekurangan
            $table->integer('sort_order')->default(0); 
            $table->timestamps();

            $table->foreign('market_analysis_id')->references('id')->on('market_analyses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_analysis_competitors');
        Schema::dropIfExists('market_analyses');
    }
};
