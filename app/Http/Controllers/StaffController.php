<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\ActivityLog;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $query = User::where('role', 'cashier'); // ← filter cashier saja

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $staff = $query->paginate(8)->withQueryString();

        // Stat cards
        $totalStaff    = User::where('role', 'cashier')->count(); // ← filter cashier
        $onDuty        = Shift::whereDate('started_at', $today)->whereNull('ended_at')->count();
        $avgTrans      = 0;
        $onDutyShifts  = Shift::with('user')->whereNull('ended_at')->get();
        $trxPerCashier = Transaction::whereDate('created_at', $today)
            ->selectRaw('cashier_id, COUNT(*) as cnt')
            ->groupBy('cashier_id')
            ->pluck('cnt');

        if ($trxPerCashier->count() > 0) {
            $avgTrans = round($trxPerCashier->avg());
        }

        // Shift hari ini
        $todayShifts = Shift::with('user')
            ->whereDate('started_at', $today)
            ->get()
            ->groupBy('type')
            ->map(fn($group) => $group->take(1));

        // Top 3 performers hari ini
        $topPerformers = User::where('role', 'cashier') // ← filter cashier
            ->withCount([
                'transactions as trx_today' => fn($q) =>
                    $q->whereDate('created_at', $today)->where('status', 'completed')
            ])
            ->withSum([
                'transactions as revenue_today' => fn($q) =>
                    $q->whereDate('created_at', $today)->where('status', 'completed')
            ], 'total')
            ->orderByDesc('trx_today')
            ->limit(3)
            ->get();

        $activityQuery = ActivityLog::with('user')->latest();

        if ($request->filled('log_from')) {
            $activityQuery->whereDate('created_at', '>=', $request->log_from);
        }
        if ($request->filled('log_to')) {
            $activityQuery->whereDate('created_at', '<=', $request->log_to);
        }

        $activityLogs = $activityQuery->limit(20)->get();

        return view('pages.staff', compact(
            'staff', 'totalStaff', 'onDuty', 'onDutyShifts', 'avgTrans',
            'todayShifts', 'topPerformers', 'activityLogs'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'shift'    => 'required|in:pagi,siang',
            'password' => 'required|string|min:8',
            'photo'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff-photos', 'public');
        }

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => 'cashier',
            'phone'    => $request->phone,
            'shift'    => $request->shift,
            'status'   => 'active',
            'password' => Hash::make($request->password),
            'photo'    => $photoPath,
        ]);

        return back()->with('success', 'Staff berhasil ditambahkan.');
    }

    public function update(Request $request, User $staff)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'shift'  => 'required|in:pagi,siang',
            'status' => 'required|in:active,inactive',
            'photo'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->status === 'inactive') {
            if ($staff->id === auth()->id()) {
                return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }
            if ($staff->role === 'admin') {
                return back()->with('error', 'Akun administrator utama tidak dapat dinonaktifkan.');
            }
        }

        $data = $request->only('name', 'phone', 'shift', 'status');

        if ($request->hasFile('photo')) {
            // Hapus foto lama kalau ada, biar storage nggak numpuk file yatim
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $data['photo'] = $request->file('photo')->store('staff-photos', 'public');
        }

        $staff->update($data);

        return back()->with('success', 'Data staff berhasil diperbarui.');
    }
}