<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActivityLog;

class ShiftController extends Controller
{
    // Daftar semua shift (untuk admin)
    public function index(Request $request)
    {
        $today = Carbon::today();

        $query = Shift::with('user')->latest('started_at');

        if ($request->filled('date')) {
            $query->whereDate('started_at', $request->date);
        } else {
            $query->whereDate('started_at', $today);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $shifts = $query->paginate(10)->withQueryString();

        // Ringkasan shift hari ini
        $shiftSummary = Shift::with('user')
            ->whereDate('started_at', $today)
            ->get()
            ->groupBy('type');

        // Staff yang belum assign shift hari ini
        $assignedUserIds = Shift::whereDate('started_at', $today)->pluck('user_id');
        $unassignedStaff = User::whereNotIn('id', $assignedUserIds)
            ->where('status', 'active')
            ->get();

        return view('pages.shifts', compact('shifts', 'shiftSummary', 'unassignedStaff'));
    }

    // Kasir clock in — mulai shift
    public function clockIn(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pagi,siang,malam',
        ]);

        $userId = auth()->id();
        $today  = Carbon::today();

        // Cek apakah sudah ada shift aktif
        $activeShift = Shift::where('user_id', $userId)
            ->whereDate('started_at', $today)
            ->whereNull('ended_at')
            ->first();

        if ($activeShift) {
            return back()->with('error', 'Kamu sudah memiliki shift aktif hari ini.');
        }

        Shift::create([
            'user_id'    => $userId,
            'type'       => $request->type,
            'started_at' => now(),
        ]);
        ActivityLog::record('SHIFT', 'Clock in shift ' . $request->type, auth()->user()->name);


        return back()->with('success', 'Clock in berhasil. Shift dimulai.');
    }

    // Kasir clock out — akhiri shift
    public function clockOut()
    {
        $userId = auth()->id();

        $activeShift = Shift::where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (!$activeShift) {
            return back()->with('error', 'Tidak ada shift aktif yang bisa diakhiri.');
        }

        // Hitung total transaksi selama shift berlangsung
        $shiftRevenue = Transaction::where('cashier_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $activeShift->started_at)
            ->sum('total');

        $shiftTrxCount = Transaction::where('cashier_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $activeShift->started_at)
            ->count();

        $activeShift->update([
            'ended_at'   => now(),
            'revenue'    => $shiftRevenue,
            'trx_count'  => $shiftTrxCount,
        ]);
        ActivityLog::record(
            'SHIFT',
            'Clock out shift',
            auth()->user()->name,
            ['revenue' => $shiftRevenue, 'trx_count' => $shiftTrxCount]
        );

        return back()->with('success', "Shift selesai. Total: {$shiftTrxCount} transaksi, Rp " . number_format($shiftRevenue));
    }

    // Admin assign shift manual ke staff tertentu
    public function store(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'type'       => 'required|in:pagi,siang,malam',
            'started_at' => 'required|date',
        ]);

        $existing = Shift::where('user_id', $request->user_id)
            ->whereDate('started_at', Carbon::parse($request->started_at)->toDateString())
            ->first();

        if ($existing) {
            return back()->with('error', 'Staff ini sudah memiliki shift pada tanggal tersebut.');
        }

        Shift::create([
            'user_id'    => $request->user_id,
            'type'       => $request->type,
            'started_at' => $request->started_at,
        ]);

        return back()->with('success', 'Shift berhasil ditambahkan.');
    }

    // Update shift (ganti tipe atau waktu)
    public function update(Request $request, Shift $shift)
    {
        $request->validate([
            'type'       => 'required|in:pagi,siang,malam',
            'started_at' => 'required|date',
        ]);

        // Tidak boleh edit shift yang sudah selesai
        if ($shift->ended_at) {
            return back()->with('error', 'Shift yang sudah selesai tidak bisa diedit.');
        }

        $shift->update($request->only('type', 'started_at'));

        return back()->with('success', 'Shift berhasil diperbarui.');
    }

    // Hapus shift (hanya yang belum dimulai / belum clock in)
    public function destroy(Shift $shift)
    {
        if ($shift->ended_at) {
            return back()->with('error', 'Shift yang sudah selesai tidak bisa dihapus.');
        }

        $shift->delete();

        return back()->with('success', 'Shift berhasil dihapus.');
    }

    // Laporan ringkasan shift (dipanggil dari ReportController juga)
    public function summary(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        $summary = Shift::with(['user', 'transactions'])
            ->whereDate('started_at', $date)
            ->get()
            ->groupBy('type')
            ->map(fn($shifts, $type) => [
                'type'      => $type,
                'staff'     => $shifts->count(),
                'trx'       => $shifts->sum('trx_count'),
                'revenue'   => $shifts->sum('revenue'),
                'avg'       => $shifts->count() > 0
                    ? $shifts->sum('revenue') / $shifts->count()
                    : 0,
            ]);

        return response()->json($summary);
    }
}