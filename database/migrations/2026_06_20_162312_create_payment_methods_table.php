<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    if (!Schema::hasTable('payment_methods')) {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['digital', 'cash', 'edc']);
            $table->string('provider')->nullable();
            $table->decimal('mdr_fee', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};