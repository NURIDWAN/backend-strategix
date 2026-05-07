<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('pdf_access_expires_at')->nullable();
            $table->string('pdf_access_package')->nullable(); // 'monthly' or 'yearly'
            $table->boolean('pdf_access_active')->default(false);

            $table->index('pdf_access_expires_at');
            $table->index('pdf_access_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['pdf_access_expires_at']);
            $table->dropIndex(['pdf_access_active']);
            $table->dropColumn(['pdf_access_expires_at', 'pdf_access_package', 'pdf_access_active']);
        });
    }
};
