<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProfile extends Model
{
    protected $table = 'store_profile';
    protected $fillable = ['store_name', 'store_subtitle', 'address', 'phone', 'city'];

    // Helper static untuk ambil profile (selalu 1 baris)
    public static function get(): self
    {
        return static::firstOrCreate([], [
            'store_name' => 'SITOKO2',
        ]);
    }
}
