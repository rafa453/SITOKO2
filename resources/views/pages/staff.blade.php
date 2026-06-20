@extends('layouts.app')

@section('title', 'Staff Management')
@section('page-title', 'Staff Management')
@section('page-subtitle', 'Manage cashier accounts, roles, and shift assignments.')

@section('header-actions')
    <div class="filter-bar">
        <div class="search-input-wrapper" style="width:200px">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" class="form-input" placeholder="Search staff...">
        </div>
        <select class="form-select" style="width:130px">
            <option>All Roles</option>
            <option>Admin</option>
            <option>Supervisor</option>
            <option>Cashier</option>
        </select>
        <select class="form-select" style="width:130px">
            <option>All Status</option>
            <option>Active</option>
            <option>On Leave</option>
            <option>Inactive</option>
        </select>
    </div>
    <button class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Staff
    </button>
@endsection

@section('content')

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
        <div class="stat-card__value">23</div>
        <div class="stat-card__meta text-muted text-sm">Active accounts</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">On Duty Today</span>
            <span class="status-dot status-dot--green"></span>
        </div>
        <div class="stat-card__value" style="color:var(--green-700)">04</div>
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
        <div style="font-size:22px; font-weight:800; letter-spacing:-.5px">Pagi</div>
        <div class="stat-card__meta text-muted text-sm">07.00 – 14.00 WIB</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Avg. Trans / Today</span>
            <span class="badge-trend badge-trend--up">↑ 12% vs Yesterday</span>
        </div>
        <div class="stat-card__value">42</div>
        <div class="stat-card__meta text-muted text-sm">per staff</div>
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
            $shifts = [
                ['shift'=>'Pagi',  'hours'=>'07.00 – 14.00','initial'=>'SR','name'=>'Siti Rahayu',  'status'=>'active'],
                ['shift'=>'Siang', 'hours'=>'14.00 – 21.00','initial'=>'BS','name'=>'Budi Santoso',  'status'=>'scheduled'],
                ['shift'=>'Malam', 'hours'=>'21.00 – 07.00','initial'=>null,'name'=>null,             'status'=>'vacant'],
            ];
            @endphp

            @foreach($shifts as $s)
            <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border:1px solid var(--border); border-radius:var(--radius); background:{{ $s['status']==='active' ? 'var(--border-light)' : 'var(--surface)' }}">
                <div>
                    <div style="font-size:14px; font-weight:700">{{ $s['shift'] }}</div>
                    <div style="font-size:12px; color:var(--text-muted)">{{ $s['hours'] }}</div>
                </div>
                <div style="display:flex; align-items:center; gap:10px">
                    @if($s['name'])
                        <div class="avatar avatar--blue">{{ $s['initial'] }}</div>
                        <span style="font-size:13px; font-weight:600">{{ $s['name'] }}</span>
                    @else
                        <span style="color:var(--text-muted); font-size:13px">✗ No assignment yet</span>
                    @endif
                </div>
                @if($s['status']==='active')
                    <span class="badge badge--green">Active</span>
                @elseif($s['status']==='scheduled')
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
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
            @php
            $performers = [
                ['rank'=>1,'initial'=>'SR','name'=>'Siti Rahayu',   'role'=>'ADMIN',      'trx'=>142,'rev'=>'Rp 12.4M','color'=>'avatar--blue'],
                ['rank'=>2,'initial'=>'BS','name'=>'Budi Santoso',  'role'=>'SUPERVISOR', 'trx'=>128,'rev'=>'Rp 10.1M','color'=>'avatar--green'],
                ['rank'=>3,'initial'=>'RW','name'=>'Rina Wulandari','role'=>'CASHIER',    'trx'=>115,'rev'=>'Rp 8.9M', 'color'=>'avatar--amber'],
            ];
            @endphp

            @foreach($performers as $p)
            <div style="display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-light)">
                <div style="font-size:11px; font-weight:700; color:var(--text-muted); width:20px">#{{ $p['rank'] }}</div>
                <div class="avatar {{ $p['color'] }}">{{ $p['initial'] }}</div>
                <div style="flex:1">
                    <div style="font-weight:700; font-size:13px">{{ $p['name'] }}</div>
                    <span class="badge badge--{{ $p['role']==='ADMIN' ? 'blue' : ($p['role']==='SUPERVISOR' ? 'purple' : 'gray') }}" style="margin-top:2px">{{ $p['role'] }}</span>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:700; font-size:13px">{{ $p['trx'] }} <span class="text-muted" style="font-weight:400">Trans</span></div>
                    <div style="font-size:12px; color:var(--blue-600); font-weight:600">{{ $p['rev'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ===== STAFF DIRECTORY TABLE ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Staff Directory</div>
        <div style="display:flex; gap:6px">
            <button class="btn-icon" title="Filter">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </button>
            <button class="btn-icon" title="Export">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </button>
        </div>
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
                    <th>Total Shifts</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                $staff = [
                    ['id'=>'M01-023','initial'=>'SR','name'=>'Siti Rahayu',   'role'=>'ADMIN',   'shift'=>'Pagi', 'phone'=>'0812-9922-1100','joined'=>'12 Jan 2023','shifts'=>24,'status'=>'active'],
                    ['id'=>'M01-045','initial'=>'DA','name'=>'Dewi Anggraini','role'=>'CASHIER', 'shift'=>'Malam','phone'=>'0856-2244-8899','joined'=>'05 Mar 2023','shifts'=>12,'status'=>'leave'],
                    ['id'=>'M01-082','initial'=>'FN','name'=>'Fajar Nugroho', 'role'=>'CASHIER', 'shift'=>'Pagi', 'phone'=>'0821-4433-2211','joined'=>'15 May 2023','shifts'=>22,'status'=>'active'],
                    ['id'=>'M01-012','initial'=>'AP','name'=>'Agus Prasetyo', 'role'=>'CASHIER', 'shift'=>'Malam','phone'=>'0818-0099-7788','joined'=>'20 Dec 2022','shifts'=>0, 'status'=>'inactive'],
                ];
                @endphp

                @foreach($staff as $s)
                <tr>
                    <td class="table-id">{{ $s['id'] }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px">
                            <div class="avatar avatar--blue" style="width:28px; height:28px; font-size:10px">{{ $s['initial'] }}</div>
                            <span style="font-weight:600">{{ $s['name'] }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $s['role']==='ADMIN' ? 'badge--blue' : ($s['role']==='SUPERVISOR' ? 'badge--purple' : 'badge--gray') }}">
                            {{ $s['role'] }}
                        </span>
                    </td>
                    <td class="text-secondary">{{ $s['shift'] }}</td>
                    <td class="text-secondary font-mono" style="font-size:12px">{{ $s['phone'] }}</td>
                    <td class="text-secondary">{{ $s['joined'] }}</td>
                    <td style="font-weight:600">{{ $s['shifts'] }}</td>
                    <td>
                        @if($s['status']==='active')
                            <div style="display:flex; align-items:center; gap:5px">
                                <span class="status-dot status-dot--green"></span>
                                <span style="font-size:12.5px; color:var(--green-700); font-weight:600">Active</span>
                            </div>
                        @elseif($s['status']==='leave')
                            <div style="display:flex; align-items:center; gap:5px">
                                <span class="status-dot status-dot--amber"></span>
                                <span style="font-size:12.5px; color:var(--amber-700); font-weight:600">On Leave</span>
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
                            <button class="btn-icon" title="View"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                            <button class="btn-icon" title="Edit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                            <button class="btn-icon" title="Deactivate" style="color:var(--red-500)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span class="pagination-info">Showing 1–8 of 23 staff</span>
        <div class="pagination-controls">
            <button class="page-btn" disabled>Previous</button>
            <button class="page-btn">Next</button>
        </div>
    </div>
</div>

{{-- ===== RECENT ACTIVITY LOG ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Recent Activity Log</div>
    </div>
    <div class="data-table-wrapper" style="border:none; border-radius:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Staff Name</th>
                    <th>Action</th>
                    <th>Type</th>
                    <th>Performed By</th>
                </tr>
            </thead>
            <tbody>
                @php
                $logs = [
                    ['ts'=>'2023-10-30 07:02:11','staff'=>'Siti Rahayu',   'action'=>'Clocked In – Shift Pagi','type'=>'SHIFT_EVENT','by'=>'System Auto'],
                    ['ts'=>'2023-10-29 16:45:30','staff'=>'Dewi Anggraini','action'=>'Requested Leave (1–3 Nov)','type'=>'HR_REQUEST', 'by'=>'Budi Santoso'],
                    ['ts'=>'2023-10-29 09:12:05','staff'=>'Fajar Nugroho', 'action'=>'Password Reset',           'type'=>'SECURITY',   'by'=>'Siti Rahayu'],
                ];
                @endphp
                @foreach($logs as $log)
                <tr>
                    <td class="table-id">{{ $log['ts'] }}</td>
                    <td style="font-weight:600">{{ $log['staff'] }}</td>
                    <td class="text-secondary">{{ $log['action'] }}</td>
                    <td>
                        <span class="badge {{ $log['type']==='SHIFT_EVENT' ? 'badge--green' : ($log['type']==='SECURITY' ? 'badge--red' : 'badge--blue') }}">
                            {{ $log['type'] }}
                        </span>
                    </td>
                    <td class="text-secondary">{{ $log['by'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
