<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\ActivityLog;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $query = User::where('role', 'cashier'); // ← filter cashier saja

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
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
            'role'     => 'required|in:admin,cashier',
            'phone'    => 'nullable|string|max:20',
            'shift'    => 'required|in:pagi,siang,malam',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'phone'    => $request->phone,
            'shift'    => $request->shift,
            'status'   => 'active',
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Staff berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'required|in:admin,cashier,supervisor',
            'phone' => 'nullable|string|max:20',
            'shift' => 'required|in:pagi,siang,malam',
        ]);

        $user->update($request->only('name', 'role', 'phone', 'shift'));

        return back()->with('success', 'Data staff berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->update(['status' => 'inactive']);
        return back()->with('success', 'Staff berhasil dinonaktifkan.');
    }
}