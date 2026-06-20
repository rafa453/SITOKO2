@extends('layouts.app')

@section('title', 'Payment Methods')
@section('page-title', 'Payment Methods')
@section('page-subtitle', 'Configure and monitor accepted payment channels.')

@section('header-actions')
    <select class="form-select" style="width:130px">
        <option>Status: All</option>
        <option>Active</option>
        <option>Inactive</option>
    </select>
    <button class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Payment Method
    </button>
@endsection

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid stats-grid--3">

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Active Payment Methods</span>
            <span class="status-dot status-dot--green"></span>
        </div>
        <div class="stat-card__value">8</div>
        <div class="stat-card__meta text-muted text-sm">
            <span class="badge badge--gray">CHANNELS</span>
            All primary gateways operational
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Digital (Today)</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue-600)" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
            </svg>
        </div>
        <div class="stat-card__value">142</div>
        <div class="stat-card__meta">
            <span style="color:var(--blue-600); font-weight:700; font-size:13px">68% share</span>
            <span class="badge-trend badge-trend--up" style="font-size:11px">+12% from yesterday</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Cash (Today)</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--amber-500)" stroke-width="2">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>
        <div class="stat-card__value">67</div>
        <div class="stat-card__meta">
            <span style="color:var(--amber-700); font-weight:700; font-size:13px">32% share</span>
            <span class="badge-trend badge-trend--down" style="font-size:11px">-4% from yesterday</span>
        </div>
    </div>

</div>

{{-- ===== PAYMENT METHOD CARDS ===== --}}
@php
$methods = [
    ['name'=>'GoPay',     'type'=>'DIGITAL','color'=>'#00AED6','trx'=>42,'rev'=>'Rp2.4M','last'=>'14:22:05','active'=>true],
    ['name'=>'OVO',       'type'=>'DIGITAL','color'=>'#4C3494','trx'=>28,'rev'=>'Rp1.1M','last'=>'13:58:12','active'=>true],
    ['name'=>'QRIS',      'type'=>'DIGITAL','color'=>'#E8192C','trx'=>72,'rev'=>'Rp4.8M','last'=>'14:15:30','active'=>true],
    ['name'=>'Debit/EDC', 'type'=>'DIGITAL','color'=>'#2563EB','trx'=>12,'rev'=>'Rp3.1M','last'=>'11:20:45','active'=>true],
    ['name'=>'Cash',      'type'=>'CASH',   'color'=>'#F59E0B','trx'=>67,'rev'=>'Rp5.2M','last'=>'14:38:00','active'=>false],
];
@endphp

<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px">
    @foreach($methods as $m)
    <div class="card">
        <div class="card-body">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px">
                <div style="display:flex; align-items:center; gap:10px">
                    <div style="width:36px; height:36px; border-radius:8px; background:{{ $m['color'] }}22; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:{{ $m['color'] }}">
                        {{ strtoupper(substr($m['name'],0,2)) }}
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:14px">{{ $m['name'] }}</div>
                        <span class="badge {{ $m['type']==='CASH' ? 'badge--amber' : 'badge--blue' }}" style="font-size:10px">{{ $m['type'] }}</span>
                    </div>
                </div>
                <label class="toggle">
                    <input type="checkbox" {{ $m['active'] ? 'checked' : '' }}>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px">
                <div>
                    <div style="font-size:10px; font-weight:600; text-transform:uppercase; color:var(--text-muted); letter-spacing:.5px">TRX Today</div>
                    <div style="font-size:20px; font-weight:800; margin-top:2px">{{ $m['trx'] }}</div>
                </div>
                <div>
                    <div style="font-size:10px; font-weight:600; text-transform:uppercase; color:var(--text-muted); letter-spacing:.5px">Revenue</div>
                    <div style="font-size:20px; font-weight:800; margin-top:2px">{{ $m['rev'] }}</div>
                </div>
            </div>

            <div style="font-size:11.5px; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:4px">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                Last used: {{ $m['last'] }}
            </div>

            <div style="display:flex; gap:6px">
                <button class="btn btn--secondary btn--sm" style="flex:1; justify-content:center">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit Settings
                </button>
                <button class="btn-icon" title="View details">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ===== PERFORMANCE TABLE ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Performance Comparison Today</div>
    </div>
    <div class="data-table-wrapper" style="border:none; border-radius:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Type</th>
                    <th>Transactions</th>
                    <th>Revenue (Rp)</th>
                    <th>Avg. Value</th>
                    <th>% of Total Revenue</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                $perf = [
                    ['name'=>'GoPay','type'=>'DIGITAL','trx'=>42, 'rev'=>'2.400.000','avg'=>'57.142','pct'=>14,'bar_color'=>'#2563EB','pct_w'=>14],
                    ['name'=>'Cash', 'type'=>'CASH',   'trx'=>67, 'rev'=>'5.200.000','avg'=>'77.611','pct'=>32,'bar_color'=>'#F59E0B','pct_w'=>32],
                    ['name'=>'QRIS', 'type'=>'DIGITAL','trx'=>72, 'rev'=>'4.800.000','avg'=>'66.666','pct'=>29,'bar_color'=>'#EF4444','pct_w'=>29],
                ];
                @endphp

                @foreach($perf as $p)
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px">
                            <div style="width:20px; height:20px; border-radius:4px; background:{{ $p['bar_color'] }}22; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:800; color:{{ $p['bar_color'] }}">
                                {{ strtoupper(substr($p['name'],0,2)) }}
                            </div>
                            <span style="font-weight:600">{{ $p['name'] }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $p['type']==='CASH' ? 'badge--amber' : 'badge--blue' }}">{{ $p['type'] }}</span>
                    </td>
                    <td style="font-weight:600">{{ $p['trx'] }}</td>
                    <td style="font-weight:600">{{ $p['rev'] }}</td>
                    <td class="text-secondary">{{ $p['avg'] }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px">
                            <div class="progress-bar" style="width:80px; flex-shrink:0">
                                <div class="progress-bar__fill" style="width:{{ $p['pct_w'] }}%; background:{{ $p['bar_color'] }}"></div>
                            </div>
                            <span style="font-size:12px; font-weight:600">{{ $p['pct'] }}%</span>
                        </div>
                    </td>
                    <td><div style="display:flex; align-items:center; gap:5px"><span class="status-dot status-dot--green"></span><span style="font-size:12.5px; color:var(--green-700); font-weight:600">Active</span></div></td>
                    <td>
                        <button class="btn-icon">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                @endforeach

                {{-- Total row --}}
                <tr style="background:var(--sidebar-bg)">
                    <td colspan="2" style="font-weight:800; color:#fff; font-size:12px; text-transform:uppercase; letter-spacing:.5px">Total Revenue (Today)</td>
                    <td style="font-weight:800; color:#fff">209</td>
                    <td style="font-weight:800; color:#fff">Rp16.600.000</td>
                    <td style="color:#64748B">79.425</td>
                    <td colspan="3">
                        <div class="progress-bar" style="background:rgba(255,255,255,.1)">
                            <div class="progress-bar__fill" style="width:100%; background:#2563EB"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ===== 7-DAY TREND + ADD NEW FORM ===== --}}
<div class="card-grid card-grid--2">

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">7-Day Transaction Trend</div>
                <div class="card-subtitle">Daily Volume by Method</div>
            </div>
            <div style="display:flex; gap:10px; font-size:11.5px; align-items:center">
                <span style="display:flex; align-items:center; gap:4px"><span class="status-dot" style="background:#2563EB"></span> GP</span>
                <span style="display:flex; align-items:center; gap:4px"><span class="status-dot" style="background:#F59E0B"></span> CS</span>
                <span style="display:flex; align-items:center; gap:4px"><span class="status-dot" style="background:#EF4444"></span> QR</span>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-wrapper" style="height:200px">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Add New Payment Method</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
            <div class="card-grid card-grid--2" style="gap:10px">
                <div class="form-group">
                    <label class="form-label">Method Name</label>
                    <input class="form-input" type="text" placeholder="e.g. ShopeePay">
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select class="form-select">
                        <option>Digital</option>
                        <option>Cash</option>
                        <option>EDC</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Provider</label>
                    <input class="form-input" type="text" placeholder="Bank or Gateway">
                </div>
                <div class="form-group">
                    <label class="form-label">MDR Fee (%)</label>
                    <input class="form-input" type="number" value="0.70" step="0.01">
                </div>
            </div>

            <label style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm)">
                <span style="font-size:13px; font-weight:500">Initial Status: Active</span>
                <label class="toggle">
                    <input type="checkbox" checked>
                    <span class="toggle-slider"></span>
                </label>
            </label>

            <div class="form-group">
                <label class="form-label">Internal Notes</label>
                <textarea class="form-input" rows="2" placeholder="Optional notes for administration..." style="resize:none"></textarea>
            </div>

            <div style="display:flex; gap:8px; justify-content:flex-end; border-top:1px solid var(--border-light); padding-top:12px">
                <button class="btn btn--secondary">Cancel</button>
                <button class="btn btn--primary">Save Channel</button>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;

    const labels = ['MON','TUE','WED','THU','FRI','SAT','TODAY'];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label:'GoPay', data:[30,35,28,40,38,45,42], backgroundColor:'#2563EB', borderRadius:4 },
                { label:'Cash',  data:[50,45,60,55,48,70,67], backgroundColor:'#F59E0B', borderRadius:4 },
                { label:'QRIS',  data:[55,60,65,70,68,80,72], backgroundColor:'#EF4444', borderRadius:4 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, stacked: false, ticks: { font:{size:11}, color:'#94A3B8' } },
                y: { grid: { color:'#F1F5F9' }, ticks: { font:{size:11}, color:'#94A3B8' } }
            }
        }
    });
});
</script>
@endpush
