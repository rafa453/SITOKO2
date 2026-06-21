<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pagi', 'siang', 'malam']);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();  // null = masih aktif
            $table->bigInteger('revenue')->default(0);  // total revenue selama shift
            $table->integer('trx_count')->default(0);   // total transaksi selama shift
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};