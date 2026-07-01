<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\Shift;
use Carbon\Carbon;
use App\Models\ActivityLog;

class AutoClockInCashier
{
    public function handle(Login $event)
    {
        $user = $event->user;

        // Cuma berlaku buat role cashier
        if ($user->role !== 'cashier') {
            return;
        }

        $today = Carbon::today();

        // Skip kalau sudah ada shift aktif hari ini
        $activeShift = Shift::where('user_id', $user->id)
            ->whereDate('started_at', $today)
            ->whereNull('ended_at')
            ->first();

        if ($activeShift) {
            return;
        }

        // Tentukan tipe shift otomatis berdasarkan jam sekarang
        $hour = now()->hour;
        if ($hour >= 7 && $hour < 15) {
            $type = 'pagi';
        } elseif ($hour >= 15 && $hour < 23) {
            $type = 'siang';
        } else {
            $type = 'malam';
        }

        Shift::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'started_at' => now(),
        ]);

        ActivityLog::record('SHIFT', 'Auto clock in shift ' . $type, $user->name);
    }
}