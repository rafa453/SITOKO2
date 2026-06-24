<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('qty');
        });

        // Backfill dari products.unit berdasarkan product_id
        DB::statement('
            UPDATE transaction_items ti
            JOIN products p ON ti.product_id = p.id
            SET ti.unit = p.unit
            WHERE ti.unit IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};