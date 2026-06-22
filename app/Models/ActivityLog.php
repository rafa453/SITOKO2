<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'action',
        'subject',
        'meta',
        'ip',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper static method supaya logging lebih ringkas
    public static function record(string $type, string $action, ?string $subject = null, ?array $meta = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'type'    => $type,
            'action'  => $action,
            'subject' => $subject,
            'meta'    => $meta,
            'ip'      => request()->ip(),
        ]);
    }
}