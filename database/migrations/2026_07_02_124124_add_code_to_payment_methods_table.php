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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name');
        });
        
        // Populate existing rows with a code derived from name
        foreach (\App\Models\PaymentMethod::all() as $pm) {
            $pm->code = \Illuminate\Support\Str::slug($pm->name);
            $pm->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
