<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_profile', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('SITOKO2');
            $table->string('store_subtitle')->nullable()->default('Toko Sembako');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        DB::table('store_profile')->insert([
            'store_name'     => 'SITOKO2',
            'store_subtitle' => 'Toko Sembako',
            'address'        => 'Jl. Contoh No. 1, Bogor',
            'phone'          => '0812-xxxx-xxxx',
            'city'           => 'Bogor',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_profile');
    }
};
