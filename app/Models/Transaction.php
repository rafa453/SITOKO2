<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'total' => 'integer',
        'amount_paid' => 'integer',
        'change' => 'integer',
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

    /**
     * Batasi query ke transaksi milik kasir sendiri. Admin/supervisor melihat semua.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user && $user->isCashier()) {
            $query->where('cashier_id', $user->id);
        }

        return $query;
    }

    /**
     * Cek apakah transaksi boleh dilihat user. Kasir hanya miliknya sendiri.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ! $user->isCashier() || $this->cashier_id === $user->id;
    }
}
