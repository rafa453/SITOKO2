<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'category',
        'is_active',
        'brand',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brands()
    {
        return $this->belongsToMany(Brand::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function supplierReturns()
    {
        return $this->hasMany(SupplierReturn::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
                    ->withPivot(['supplier_sku', 'price'])
                    ->withTimestamps();
    }
}