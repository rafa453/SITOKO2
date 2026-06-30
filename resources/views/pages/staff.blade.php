@extends('layouts.app')

@section('title', 'Staff Management')
@section('page-title', 'Staff Management')
@section('page-subtitle', 'Manage cashier accounts, roles, and shift assignments.')

@section('header-actions')
    <form method="GET" action="{{ route('staff.index') }}" class="filter-bar" id="staffFilterForm">
        <div class="search-input-wrapper" style="width:200px">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="search" class="form-input" placeholder="Search staff..."
                value="{{ request('search') }}" onchange="this.form.submit()">
        </div>
        {{-- <select name="role" class="form-select" style="width:130px" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <option value="admin"      {{ request('role') == 'admin'      ? 'selected' : '' }}>Admin</option>
            <option value="cashier"    {{ request('role') == 'cashier'    ? 'selected' : '' }}>Cashier</option>
        </select> --}}
        <select name="status" class="form-select" style="width:130px" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active"   {{ request('status') == 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </form>

    <button class="btn btn--primary"
        onclick="document.getElementById('modalAddStaff').style.display='flex'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Staff
    </button>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Staff</span>
            <div class="stat-card__icon" style="background:#EFF6FF">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value">{{ $totalStaff }}</div>
        <div class="stat-card__meta text-muted text-sm">Registered accounts</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">On Duty Today</span>
            <span class="status-dot status-dot--green"></span>
        </div>
        <div class="stat-card__value" style="color:var(--green-700)">
            {{ str_pad($onDuty, 2, '0', STR_PAD_LEFT) }}
        </div>
        <div class="stat-card__meta text-sm" style="color:var(--green-700); font-weight:600">Currently clocked in</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Active Shift</span>
            <div class="stat-card__icon" style="background:#FFF7ED">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
        </div>
        @php
            $hour = now()->hour;
            if ($hour >= 7 && $hour < 15)       { $shiftName = 'Pagi';  $shiftHours = '07.00 – 15.00 WIB'; }
            elseif ($hour >= 15 && $hour < 23)  { $shiftName = 'Siang'; $shiftHours = '15.00 – 23.00 WIB'; }
            else                                 { $shiftName = 'Malam'; $shiftHours = '23.00 – 07.00 WIB'; }
        @endphp
        <div style="font-size:22px; font-weight:800; letter-spacing:-.5px">{{ $shiftName }}</div>
        <div class="stat-card__meta text-muted text-sm">{{ $shiftHours }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Avg. Trans / Today</span>
        </div>
        <div class="stat-card__value">{{ $avgTrans }}</div>
        <div class="stat-card__meta text-muted text-sm">per active cashier</div>
    </div>

</div>

{{-- ===== SHIFT SCHEDULE + TOP PERFORMERS ===== --}}
<div class="card-grid card-grid--60-40">

    {{-- Today's Shift Schedule --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Today's Shift Schedule</div>
            <a href="#" class="card-action-link">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit Schedule
            </a>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:10px">
            @php
                $shiftConfig = [
                    'pagi'  => ['label' => 'Pagi',  'hours' => '07.00 – 15.00'],
                    'siang' => ['label' => 'Siang', 'hours' => '15.00 – 23.00'],
                    'malam' => ['label' => 'Malam', 'hours' => '23.00 – 07.00'],
                ];
                $currentShiftType = strtolower($shiftName);
            @endphp

            @foreach($shiftConfig as $type => $config)
                @php
                    $shiftStaff = $todayShifts->get($type);
                    $isActive   = $type === $currentShiftType;
                @endphp
                <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px;
                            border:1px solid var(--border); border-radius:var(--radius);
                            background:{{ $isActive ? 'var(--border-light)' : 'var(--surface)' }}">
                    <div>
                        <div style="font-size:14px; font-weight:700">{{ $config['label'] }}</div>
                        <div style="font-size:12px; color:var(--text-muted)">{{ $config['hours'] }}</div>
                    </div>

                    <div style="display:flex; align-items:center; gap:10px">
                        @if($shiftStaff && $shiftStaff->count() > 0)
                            @php $sh = $shiftStaff->first(); @endphp
                            <div class="avatar avatar--blue" style="width:28px; height:28px; font-size:10px">
                                {{ strtoupper(substr($sh->user->name ?? '?', 0, 2)) }}
                            </div>
                            <span style="font-size:13px; font-weight:600">
                                {{ $sh->user->name ?? '—' }}
                            </span>
                        @else
                            <span style="color:var(--text-muted); font-size:13px">✗ No assignment yet</span>
                        @endif
                    </div>

                    @if($isActive)
                        <span class="badge badge--green">Active</span>
                    @elseif($shiftStaff && $shiftStaff->count() > 0)
                        <span class="badge badge--blue">Scheduled</span>
                    @else
                        <span class="badge badge--gray">Vacant</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Top Performers --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Top Performers</div>
            <span class="card-subtitle">TODAY</span>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
            @php
                $avatarColors = ['avatar--blue', 'avatar--green', 'avatar--amber'];
            @endphp

            @forelse($topPerformers as $i => $p)
            <div style="display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-light)">
                <div style="font-size:11px; font-weight:700; color:var(--text-muted); width:20px">#{{ $i + 1 }}</div>
                <div class="avatar {{ $avatarColors[$i % 3] }}">
                    {{ strtoupper(substr($p->name, 0, 2)) }}
                </div>
                <div style="flex:1">
                    <div style="font-weight:700; font-size:13px">{{ $p->name }}</div>
                    <span class="badge badge--{{ $p->role === 'admin' ? 'blue' : ($p->role === 'supervisor' ? 'purple' : 'gray') }}" style="margin-top:2px">
                        {{ strtoupper($p->role) }}
                    </span>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:700; font-size:13px">
                        {{ $p->trx_today ?? 0 }}
                        <span class="text-muted" style="font-weight:400">Trans</span>
                    </div>
                    <div style="font-size:12px; color:var(--blue-600); font-weight:600">
                        Rp {{ number_format($p->revenue_today ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>
            @empty
                <p class="text-muted text-sm">No transaction data for today.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ===== STAFF DIRECTORY TABLE ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Staff Directory</div>
    </div>

    <div class="data-table-wrapper" style="border:none; border-radius:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Staff ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Assigned Shift</th>
                    <th>Phone Number</th>
                    <th>Date Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $s)
                <tr>
                    <td class="table-id">#{{ str_pad($s->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px">
                            <div class="avatar avatar--blue" style="width:28px; height:28px; font-size:10px">
                                {{ strtoupper(substr($s->name, 0, 2)) }}
                            </div>
                            <div>
                                <div style="font-weight:600; font-size:13px">{{ $s->name }}</div>
                                <div style="font-size:11px; color:var(--text-muted)">{{ $s->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $s->role === 'admin' ? 'badge--blue' : ($s->role === 'supervisor' ? 'badge--purple' : 'badge--gray') }}">
                            {{ strtoupper($s->role) }}
                        </span>
                    </td>
                    <td class="text-secondary">{{ ucfirst($s->shift) }}</td>
                    <td class="text-secondary" style="font-size:12px">{{ $s->phone ?? '—' }}</td>
                    <td class="text-secondary">{{ $s->created_at->format('d M Y') }}</td>
                    <td>
                        @if($s->status === 'active')
                            <div style="display:flex; align-items:center; gap:5px">
                                <span class="status-dot status-dot--green"></span>
                                <span style="font-size:12.5px; color:var(--green-700); font-weight:600">Active</span>
                            </div>
                        @else
                            <div style="display:flex; align-items:center; gap:5px">
                                <span class="status-dot status-dot--gray"></span>
                                <span style="font-size:12.5px; color:var(--text-muted); font-weight:600">Inactive</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:4px">
                            {{-- Edit --}}
                            <button class="btn-icon" title="Edit"
                                onclick="openEditModal({{ $s->id }}, '{{ addslashes($s->name) }}', '{{ $s->role }}', '{{ $s->phone }}', '{{ $s->shift }}')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>

                            {{-- Deactivate --}}
                            @if($s->status === 'active')
                            <form method="POST" action="{{ route('staff.destroy', $s->id) }}"
                                  onsubmit="return confirm('Nonaktifkan {{ addslashes($s->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon" title="Deactivate" style="color:var(--red-500)">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted)">
                            No staff found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="padding:0 20px">
        {{ $staff->links() }}
    </div>
</div>

{{-- ===== RECENT ACTIVITY LOG ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Recent Activity Log</div>
        <span class="card-subtitle">Last 20 activities</span>
    </div>
        <div style="padding:12px 20px; border-bottom:1px solid var(--border-light)">
        <form method="GET" action="{{ route('staff.index') }}" style="display:flex; align-items:center; gap:8px">
            {{-- Pertahankan filter staff yang sudah ada --}}
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="role" value="{{ request('role') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">

            <span style="font-size:12px; font-weight:600; color:var(--text-secondary)">Filter Log:</span>
            <input
                type="date"
                name="log_from"
                class="form-input"
                style="width:145px"
                value="{{ request('log_from') }}"
            >
            <span style="font-size:12px; color:var(--text-muted)">—</span>
            <input
                type="date"
                name="log_to"
                class="form-input"
                style="width:145px"
                value="{{ request('log_to') }}"
            >
            <button type="submit" class="btn btn--secondary btn--sm">Apply</button>
            @if(request('log_from') || request('log_to'))
                <a href="{{ route('staff.index') }}" class="btn btn--secondary btn--sm">Reset</a>
            @endif
        </form>
    </div>
    <div class="data-table-wrapper" style="border:none; border-radius:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Staff</th>
                    <th>Action</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activityLogs as $log)
                <tr>
                    <td class="table-id">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:7px">
                            <div class="avatar avatar--blue" style="width:26px; height:26px; font-size:10px">
                                {{ strtoupper(substr($log->user?->name ?? '?', 0, 2)) }}
                            </div>
                            <span style="font-size:12.5px; font-weight:600">{{ $log->user?->name ?? 'System' }}</span>
                        </div>
                    </td>
                    <td class="text-secondary">{{ $log->action }}</td>
                    <td class="text-secondary">{{ $log->subject ?? '—' }}</td>
                    <td>
                        @php
                            $badgeClass = match($log->type) {
                                'LOGIN'       => 'badge--blue',
                                'TRANSACTION' => 'badge--green',
                                'VOID'        => 'badge--red',
                                'RESTOCK'     => 'badge--amber',
                                'PRODUCT'     => 'badge--purple',
                                'SHIFT'       => 'badge--green',
                                default       => 'badge--blue',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $log->type }}</span>
                    </td>
                    <td class="table-id">{{ $log->ip ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted)">
                        No activity recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== MODAL: ADD STAFF ===== --}}
<div id="modalAddStaff" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
     z-index:999; align-items:center; justify-content:center">
    <div style="background:var(--surface); border-radius:var(--radius-lg); padding:28px; width:440px; max-width:95vw">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <div style="font-size:15px; font-weight:700">Add New Staff</div>
            <button onclick="document.getElementById('modalAddStaff').style.display='none'"
                    style="background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:18px">✕</button>
        </div>
        <form method="POST" action="{{ route('staff.store') }}" style="display:flex; flex-direction:column; gap:14px">
            @csrf
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Full Name</label>
                <input type="text" name="name" class="form-input w-full" required placeholder="e.g. Budi Santoso">
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Email</label>
                <input type="email" name="email" class="form-input w-full" required placeholder="email@example.com">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                <div>
                    <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Role</label>
                    <select name="role" class="form-select w-full" required>
                        <option value="cashier">Cashier</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Shift</label>
                    <select name="shift" class="form-select w-full" required>
                        <option value="pagi">Pagi</option>
                        <option value="siang">Siang</option>
                        <option value="malam">Malam</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Phone</label>
                <input type="text" name="phone" class="form-input w-full" placeholder="0812-xxxx-xxxx">
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Password</label>
                <input type="password" name="password" class="form-input w-full" required minlength="8" placeholder="Min. 8 characters">
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:4px">
                <button type="button" class="btn btn--secondary"
                    onclick="document.getElementById('modalAddStaff').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn--primary">Add Staff</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== MODAL: EDIT STAFF ===== --}}
<div id="modalEditStaff" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
     z-index:999; align-items:center; justify-content:center">
    <div style="background:var(--surface); border-radius:var(--radius-lg); padding:28px; width:440px; max-width:95vw">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <div style="font-size:15px; font-weight:700">Edit Staff</div>
            <button onclick="document.getElementById('modalEditStaff').style.display='none'"
                    style="background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:18px">✕</button>
        </div>
        <form id="editStaffForm" method="POST" style="display:flex; flex-direction:column; gap:14px">
            @csrf
            @method('PATCH')
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Full Name</label>
                <input type="text" id="editName" name="name" class="form-input w-full" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                <div>
                    <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Role</label>
                    <select id="editRole" name="role" class="form-select w-full" required>
                        <option value="cashier">Cashier</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Shift</label>
                    <select id="editShift" name="shift" class="form-select w-full" required>
                        <option value="pagi">Pagi</option>
                        <option value="siang">Siang</option>
                        <option value="malam">Malam</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:5px">Phone</label>
                <input type="text" id="editPhone" name="phone" class="form-input w-full">
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:4px">
                <button type="button" class="btn btn--secondary"
                    onclick="document.getElementById('modalEditStaff').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn--primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, role, phone, shift) {
    const form = document.getElementById('editStaffForm');
    form.action = '/staff/' + id;
    document.getElementById('editName').value  = name;
    document.getElementById('editPhone').value = phone || '';
    document.getElementById('editRole').value  = role;
    document.getElementById('editShift').value = shift;
    document.getElementById('modalEditStaff').style.display = 'flex';
}
</script>

@endsection