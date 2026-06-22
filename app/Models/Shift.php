<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'started_at',
        'ended_at',
        'revenue',
        'trx_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'cashier_id', 'user_id')
            ->where('created_at', '>=', $this->started_at)
            ->where('created_at', '<=', $this->ended_at ?? now());
    }
}