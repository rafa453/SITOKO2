<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('notifications:cleanup')]
#[Description('Hapus notifikasi yang sudah dibaca (read_at tidak null) dan lebih lama dari 30 hari.')]
class CleanupNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limitDate = now()->subDays(30);

        $deletedCount = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $limitDate)
            ->delete();

        $this->info("Berhasil menghapus {$deletedCount} notifikasi yang sudah dibaca dan berusia lebih dari 30 hari.");
    }
}
