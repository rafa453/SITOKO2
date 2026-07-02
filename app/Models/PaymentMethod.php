<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'provider',
        'mdr_fee',
        'notes',
        'is_active',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payment_method', 'name');
    }
}