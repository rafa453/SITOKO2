<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('unit');           // Bag, Pcs, Kg, Box, dll
            $table->integer('qty')->default(0);
            $table->integer('threshold')->default(10); // batas low stock
            $table->bigInteger('buy_price');
            $table->bigInteger('sell_price');
            $table->string('tag')->nullable(); // STAPLE, LIQUID, DRINK, dll
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};