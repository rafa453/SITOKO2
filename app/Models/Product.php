<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function transactionItems()
    {
        return $this->hasMany(\App\Models\TransactionItem::class);
    }
}