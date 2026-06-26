<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturnItem extends Model
{
    protected $fillable = [
        'supplier_return_id', 'purchase_order_item_id',
        'product_id', 'qty_returned', 'buy_price', 'subtotal',
    ];

    public function supplierReturn()    { return $this->belongsTo(SupplierReturn::class); }
    public function purchaseOrderItem() { return $this->belongsTo(PurchaseOrderItem::class); }
    public function product()           { return $this->belongsTo(Product::class); }
}