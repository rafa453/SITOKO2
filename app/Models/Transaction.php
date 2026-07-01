<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentMethod;

class Transaction extends Model
{
    protected $fillable = [
        'code',
        'cashier_id',
        'total',
        'amount_paid',
        'change',
        'payment_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'total'       => 'integer',
        'amount_paid' => 'integer',
        'change'      => 'integer',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'name');
    }
}