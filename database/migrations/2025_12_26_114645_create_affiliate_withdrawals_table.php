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
        Schema::create('affiliate_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending, processed, failed, rejected

            // Bank Details
            $table->string('bank_name');
            $table->string('bank_code')->nullable();
            $table->string('account_number');
            $table->string('account_name');

            // SingaPay Reference
            $table->string('singapay_reference')->nullable();
            $table->json('singapay_response')->nullable();

            $table->timestamp('scheduled_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_withdrawals');
    }
};
