<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['created_at', 'status']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['started_at', 'ended_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('category');
            $table->index('qty'); // Hanya berefek untuk query filter nilai statis, BUKAN untuk whereColumn
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'status']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['started_at', 'ended_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['qty']);
        });
    }
};
