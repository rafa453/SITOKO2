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
            'expired_at',
        ];

        protected $casts = [
            'qty'        => 'integer',
            'threshold'  => 'integer',
            'buy_price'  => 'integer',
            'sell_price' => 'integer',
            'is_active'  => 'boolean',
            'expired_at' => 'date',
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

        /**
         * Status kadaluarsa produk: 'expired', 'near' (≤7 hari), 'safe', atau null jika tidak diset.
         */
        public function getExpiryStatusAttribute(): ?string
        {
            if (!$this->expired_at) {
                return null;
            }

            if ($this->expired_at->isPast()) {
                return 'expired';
            }

            if ($this->expired_at->diffInDays(now()) <= 7) {
                return 'near';
            }

            return 'safe';
        }

        public static function generateSku(string $category, string $brandName, string $supplierName): string
        {
            $kat = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $category), 0, 3));
            $mrk = $brandName === 'NOBRAND' ? 'NOB' : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $brandName), 0, 3));
            $sup = $supplierName === 'NOSUPP' ? 'NOS' : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $supplierName), 0, 3));
            $tgl = now()->format('dmy');

            $baseSku = "{$kat}-{$mrk}-{$sup}-{$tgl}";
            $sku = $baseSku;

            if (static::where('sku', 'like', $baseSku.'%')->count() > 0) {
                $counter = 1;
                $sku = $baseSku . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
                while (static::where('sku', $sku)->exists()) {
                    $counter++;
                    $sku = $baseSku . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
                }
            }

            return $sku;
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