<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50);        // LOGIN, TRANSACTION, RESTOCK, VOID, PRODUCT, SHIFT
            $table->string('action');           // deskripsi singkat
            $table->string('subject')->nullable(); // nama produk, kode transaksi, dll
            $table->json('meta')->nullable();   // data tambahan jika perlu
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};