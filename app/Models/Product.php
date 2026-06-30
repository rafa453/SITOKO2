<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'category',
        'unit',
        'qty',
        'threshold',
        'buy_price',
        'sell_price',
        'brand_id',
        'sku',
    ];

    public function transactionItems()
    {
        return $this->hasMany(\App\Models\TransactionItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(\App\Models\PurchaseOrderItem::class);
    }

    public function supplierReturnItems()
    {
        return $this->hasMany(\App\Models\SupplierReturnItem::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
                    ->withPivot(['supplier_sku', 'price'])
                    ->withTimestamps();
    }

    public static function generateSku(string $jenis, string $merek): string
    {
        $jenis  = strtoupper(Str::slug($jenis, ''));
        $merek  = strtoupper(Str::slug($merek, ''));
        $tgl    = now()->format('Ymd');

        // Ambil nomor urut hari ini
        $count  = static::whereDate('created_at', today())->count() + 1;
        $nomor  = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "{$jenis}-{$merek}-{$tgl}-{$nomor}";
    }

    public static function generateSupplierSku(string $jenis, string $merek, string $supplier): string
    {
        $jenis    = strtoupper(Str::slug($jenis, ''));
        $merek    = strtoupper(Str::slug($merek, ''));
        $supplier = strtoupper(Str::slug($supplier, ''));
        $tgl      = now()->format('Ymd');

        $count    = ProductSupplier::whereDate('created_at', today())->count() + 1;
        $nomor    = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "{$jenis}-{$merek}-{$supplier}-{$tgl}-{$nomor}";
    }


}