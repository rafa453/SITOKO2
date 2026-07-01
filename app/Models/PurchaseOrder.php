<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'code',
        'supplier_id',
        'created_by',
        'received_by',
        'status',
        'expected_at',
        'received_at',
        'total',
        'notes',
        'payment_type',
        'payment_status',
        'amount_paid',
    ];

    protected $casts = [
        'expected_at' => 'date',
        'received_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'payment_status' => 'string',
        'payment_type' => 'string',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeOrdered(): bool
    {
        return $this->status === 'draft' && $this->items()->exists();
    }

    public function canBeReceived(): bool
    {
        // Bisa di-receive jika status ordered DAN masih ada item yang belum penuh
        if ($this->status !== 'ordered') {
            return false;
        }

        return $this->items->some(
            fn($item) => $item->qty_received < $item->qty_ordered
        );
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'ordered']);
    }
}