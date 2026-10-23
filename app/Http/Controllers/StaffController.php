<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Models\ActivityLog;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $dayStart = $today->copy()->startOfDay();
        $dayEnd = $today->copy()->endOfDay();

        $query = User::where('role', 'cashier'); // ← filter cashier saja

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
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
        $totalStaff = User::where('role', 'cashier')->count(); // ← filter cashier
        $onDuty = Shift::whereBetween('started_at', [$dayStart, $dayEnd])
            ->whereNull('ended_at')
            ->count();

        $trxPerCashier = Transaction::whereBetween('created_at', [$dayStart, $dayEnd])
            ->selectRaw('cashier_id, COUNT(*) as cnt')
            ->groupBy('cashier_id')
            ->pluck('cnt');

        $avgTrans = $trxPerCashier->count() > 0 ? round($trxPerCashier->avg()) : 0;

        $todayShifts = Shift::with('user')
            ->whereBetween('started_at', [$dayStart, $dayEnd])
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => $group->take(1));

        // Top 3 performers hari ini
        $topPerformers = User::where('role', 'cashier') // ← filter cashier
            ->withCount([
                'transactions as trx_today' => fn ($q) => $q->whereBetween('created_at', [$dayStart, $dayEnd])->where('status', 'completed'),
            ])
            ->withSum([
                'transactions as revenue_today' => fn ($q) => $q->whereBetween('created_at', [$dayStart, $dayEnd])->where('status', 'completed'),
            ], 'total')
            ->orderByDesc('trx_today')
            ->limit(3)
            ->get();

        $activityQuery = ActivityLog::with('user')->latest();

        if ($request->filled('log_user_id')) {
            $activityQuery->where('user_id', $request->log_user_id);
        }
        if ($request->filled('log_type')) {
            $activityQuery->where('type', $request->log_type);
        }
        if ($request->filled('log_search')) {
            $activityQuery->where(function ($q) use ($request) {
                $q->where('action', 'like', '%'.$request->log_search.'%')
                    ->orWhere('subject', 'like', '%'.$request->log_search.'%');
            });
        }

        if ($request->filled('log_from')) {
            $activityQuery->whereDate('created_at', '>=', $request->log_from);
        }
        if ($request->filled('log_to')) {
            $activityQuery->whereDate('created_at', '<=', $request->log_to);
        }

        $activityLogs = $activityQuery->limit(20)->get();
        $allUsers = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('pages.staff', compact(
            'staff', 'totalStaff', 'onDuty', 'avgTrans',
            'todayShifts', 'topPerformers', 'activityLogs', 'allUsers'
        ));
    }

    public function store(StaffStoreRequest $request)
    {
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff-photos', 'public');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'cashier',
            'phone' => $request->phone,
            'shift' => $request->shift,
            'status' => 'active',
            'password' => Hash::make($request->password),
            'photo' => $photoPath,
        ]);

        return back()->with('success', 'Staff berhasil ditambahkan.');
    }

    public function update(StaffUpdateRequest $request, User $staff)
    {
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
