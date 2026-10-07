<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE products ADD COLUMN is_low_stock TINYINT(1) GENERATED ALWAYS AS (CASE WHEN qty <= threshold AND qty > 0 THEN 1 ELSE 0 END) STORED');

        Schema::table('products', function (Blueprint $table) {
            $table->index('is_low_stock');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_low_stock']);
        });

        DB::statement('ALTER TABLE products DROP COLUMN is_low_stock');
    }
};
