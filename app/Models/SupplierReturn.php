<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    protected $fillable = [
        'code', 'purchase_order_id', 'supplier_id',
        'created_by', 'confirmed_by', 'completed_by',
        'status', 'reason', 'total',
        'confirmed_at', 'completed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function purchaseOrder()  { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier()       { return $this->belongsTo(Supplier::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }
    public function confirmer()      { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function completer()      { return $this->belongsTo(User::class, 'completed_by'); }
    public function items()          { return $this->hasMany(SupplierReturnItem::class); }

    public function canBeConfirmed(): bool { return $this->status === 'draft'; }
    public function canBeCompleted(): bool { return $this->status === 'confirmed'; }
    public function canBeCancelled(): bool { return $this->status === 'draft'; }
}