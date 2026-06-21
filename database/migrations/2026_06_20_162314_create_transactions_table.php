<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // TRX-20240521-0042
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->bigInteger('total');
            $table->bigInteger('amount_paid');
            $table->bigInteger('change')->default(0);
            $table->string('payment_method');  // nama method: GoPay, Cash, dll
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};