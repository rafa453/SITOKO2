@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Analyze store performance across sales, inventory, and staff.')

@section('header-actions')
    <div style="display:flex; gap:8px; align-items:center">
        <select class="form-select" style="width:140px">
            <option>This Month</option>
            <option>Last Month</option>
            <option>Last 7 Days</option>
            <option>Custom Range</option>
        </select>
        <select class="form-select" style="width:140px">
            <option>All Reports</option>
            <option>Sales</option>
            <option>Inventory</option>
            <option>Staff</option>
        </select>
        <button class="btn btn--primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Export PDF
        </button>
    </div>
@endsection

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Revenue This Month</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">84.500.000</div>
        </div>
        <div class="stat-card__meta">
            <span class="badge-trend badge-trend--up">↑ +12% trend</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Transactions</span>
        </div>
        <div class="stat-card__value">3,420</div>
        <div class="stat-card__meta text-muted text-sm">avg. 114 trx/day</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Best Selling Product</span>
        </div>
        <div style="font-size:16px; font-weight:700; margin-top:4px; line-height:1.3">Beras Premium 5kg</div>
        <div class="stat-card__meta">
            <span class="badge badge--blue">STAPLE</span>
            <span class="text-muted text-sm">420 units</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Net Profit Estimate</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value" style="color:var(--green-700)">12.450.000</div>
        </div>
    </div>

</div>

{{-- ===== DAILY REVENUE TREND CHART ===== --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Daily Revenue Trend</div>
        </div>
        <div style="display:flex; gap:6px">
            <button class="btn btn--secondary btn--sm active-tab" style="background:var(--blue-600); color:#fff; border-color:var(--blue-600)">Daily</button>
            <button class="btn btn--secondary btn--sm">Weekly</button>
            <button class="btn btn--secondary btn--sm">Monthly</button>
        </div>
    </div>
    <div class="card-body">
        <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px">
            Highest: Rp 4.200.000 — 12 Mei &nbsp;|&nbsp; Avg: Rp 2.850.000/day
        </div>
        <div class="chart-wrapper" style="height:220px">
            <canvas id="revenueChart"></canvas>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--text-muted); margin-top:8px; padding:0 4px">
            <span>01 Mei</span><span>08 Mei</span><span>15 Mei</span><span>22 Mei</span><span>31 Mei</span>
        </div>
    </div>
</div>

{{-- ===== TOP PRODUCTS + REVENUE BY CATEGORY + CASHIER PERFORMANCE ===== --}}
<div class="card-grid card-grid--3">

    {{-- Top Products --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Top Products</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:10px">
            @php
            $tops = [
                ['rank'=>1,'name'=>'Beras Premium 5kg',   'rev'=>'Rp 28.5M'],
                ['rank'=>2,'name'=>'Minyak Goreng 2L',    'rev'=>'Rp 19.2M'],
                ['rank'=>3,'name'=>'Indomie Goreng Crt.', 'rev'=>'Rp 12.8M'],
                ['rank'=>4,'name'=>'Gula Pasir 1kg',      'rev'=>'Rp 10.4M'],
                ['rank'=>5,'name'=>'Susu UHT 1L',         'rev'=>'Rp 8.1M'],
            ];
            @endphp
            @foreach($tops as $t)
            <div style="display:flex; align-items:center; gap:10px; padding:6px 0; border-bottom:1px solid var(--border-light)">
                <div style="width:22px; height:22px; border-radius:50%; background:{{ $t['rank']===1 ? 'var(--blue-600)' : 'var(--border-light)' }}; color:{{ $t['rank']===1 ? '#fff' : 'var(--text-secondary)' }}; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0">{{ $t['rank'] }}</div>
                <span style="flex:1; font-size:13px; font-weight:500">{{ $t['name'] }}</span>
                <span style="font-size:13px; font-weight:700; color:var(--blue-600)">{{ $t['rev'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Revenue by Category (donut placeholder) --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Revenue by Category</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; align-items:center; gap:16px">
            <div class="chart-wrapper" style="height:160px; width:160px">
                <canvas id="categoryChart"></canvas>
            </div>
            <div style="display:flex; flex-direction:column; gap:8px; width:100%">
                @php
                $cats = [
                    ['label'=>'Beras & Sembako','color'=>'#2563EB','pct'=>45],
                    ['label'=>'Bumbu',           'color'=>'#8B5CF6','pct'=>20],
                    ['label'=>'Minyak & Lemak',  'color'=>'#F59E0B','pct'=>22],
                    ['label'=>'Minuman',          'color'=>'#22C55E','pct'=>13],
                ];
                @endphp
                @foreach($cats as $c)
                <div style="display:flex; align-items:center; gap:8px; font-size:12.5px">
                    <span class="status-dot" style="background:{{ $c['color'] }}"></span>
                    <span style="flex:1">{{ $c['label'] }}</span>
                    <span style="font-weight:600">{{ $c['pct'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cashier Performance --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Cashier Performance</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
            @php
            $cashiers = [
                ['initial'=>'AN','name'=>'Andi Nugroho',  'feedback'=>'98% Positive Feedback','trx'=>1240,'rev'=>'Rp 32.4M','color'=>'avatar--blue'],
                ['initial'=>'SP','name'=>'Siti Pertiwi',  'feedback'=>'95% Positive Feedback','trx'=>1080,'rev'=>'Rp 28.1M','color'=>'avatar--green'],
                ['initial'=>'RK','name'=>'Rian Kurniawan','feedback'=>'92% Positive Feedback','trx'=>890, 'rev'=>'Rp 21.5M','color'=>'avatar--amber'],
            ];
            @endphp
            @foreach($cashiers as $c)
            <div style="padding:12px; border:1px solid var(--border); border-radius:var(--radius); display:flex; align-items:center; gap:10px">
                <div class="avatar {{ $c['color'] }}">{{ $c['initial'] }}</div>
                <div style="flex:1; min-width:0">
                    <div style="font-size:13px; font-weight:700">{{ $c['name'] }}</div>
                    <div style="font-size:11.5px; color:var(--green-700)">{{ $c['feedback'] }}</div>
                </div>
                <div style="text-align:right; flex-shrink:0">
                    <div style="font-size:12.5px; font-weight:700">{{ number_format($c['trx']) }} <span class="text-muted" style="font-weight:400; font-size:11px">trx</span></div>
                    <div style="font-size:12px; color:var(--blue-600); font-weight:600">{{ $c['rev'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ===== INVENTORY MOVEMENT + SHIFT SUMMARY ===== --}}
<div class="card-grid card-grid--2">

    <div class="card">
        <div class="card-header">
            <div class="card-title">Inventory Movement</div>
            <a href="{{ route('inventory.index') }}" class="card-action-link">View Full Report →</a>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr><th>Product</th><th>Opening</th><th>Sold</th><th>Closing</th><th>Health</th></tr>
                </thead>
                <tbody>
                    @php
                    $movements = [
                        ['name'=>'Minyak 2L',  'open'=>450,'sold'=>120,'close'=>330,'health'=>'green'],
                        ['name'=>'Gula 1kg',   'open'=>200,'sold'=>185,'close'=>15, 'health'=>'red'],
                        ['name'=>'Teh Celup',  'open'=>120,'sold'=>40, 'close'=>80, 'health'=>'amber'],
                    ];
                    @endphp
                    @foreach($movements as $m)
                    <tr>
                        <td style="font-weight:600">{{ $m['name'] }}</td>
                        <td class="text-secondary">{{ $m['open'] }}</td>
                        <td style="font-weight:600; color:{{ $m['sold']>=$m['open']*0.8 ? 'var(--red-500)' : 'var(--text-primary)' }}">{{ $m['sold'] }}</td>
                        <td style="font-weight:600">{{ $m['close'] }}</td>
                        <td><span class="status-dot status-dot--{{ $m['health'] }}"></span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Shift Summary</div>
            <a href="#" class="card-action-link">View Shift Reports →</a>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr><th>Shift</th><th>Staff</th><th>TRX</th><th>Revenue</th><th>Avg/Shift</th></tr>
                </thead>
                <tbody>
                    @php
                    $shifts = [
                        ['shift'=>'Pagi', 'staff'=>3,'trx'=>1120,'rev'=>'28.4M','avg'=>'9.4M'],
                        ['shift'=>'Siang','staff'=>4,'trx'=>1450,'rev'=>'38.2M','avg'=>'9.5M'],
                        ['shift'=>'Malam','staff'=>2,'trx'=>850, 'rev'=>'17.9M','avg'=>'8.9M'],
                    ];
                    @endphp
                    @foreach($shifts as $s)
                    <tr>
                        <td style="font-weight:600">{{ $s['shift'] }}</td>
                        <td class="text-secondary">{{ $s['staff'] }}</td>
                        <td style="font-weight:600">{{ number_format($s['trx']) }}</td>
                        <td style="font-weight:600; color:var(--blue-600)">Rp {{ $s['rev'] }}</td>
                        <td class="text-secondary">Rp {{ $s['avg'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ===== SCHEDULED REPORTS + CUSTOM BUILDER ===== --}}
<div class="card-grid card-grid--2">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg width="14" height="14" style="display:inline; margin-right:5px; vertical-align:middle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Saved / Scheduled Reports
            </div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:8px">
            @php
            $saved = [
                ['icon'=>'📄','title'=>'Monthly Sales Summary',        'sub'=>'Scheduled: 1st of every month'],
                ['icon'=>'⚠️','title'=>'Low Stock Alert Report',       'sub'=>'Frequency: Daily at 08:00 AM'],
                ['icon'=>'💰','title'=>'Staff Incentive Calculation',  'sub'=>'Last Generated: 2 days ago'],
            ];
            @endphp
            @foreach($saved as $r)
            <div style="display:flex; align-items:center; gap:12px; padding:12px 14px; border:1px solid var(--border); border-radius:var(--radius); cursor:pointer; transition:background .15s" onmouseover="this.style.background='var(--border-light)'" onmouseout="this.style.background=''">
                <span style="font-size:18px">{{ $r['icon'] }}</span>
                <div>
                    <div style="font-size:13px; font-weight:600">{{ $r['title'] }}</div>
                    <div style="font-size:12px; color:var(--text-muted)">{{ $r['sub'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">✦ Custom Report Builder</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
            <div class="card-grid card-grid--2" style="gap:10px">
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select class="form-select">
                        <option>Sales Analysis</option>
                        <option>Inventory</option>
                        <option>Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date Range</label>
                    <select class="form-select">
                        <option>Last 30 Days</option>
                        <option>Last 7 Days</option>
                        <option>This Month</option>
                        <option>Custom</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Group By</label>
                    <select class="form-select">
                        <option>Category</option>
                        <option>Product</option>
                        <option>Cashier</option>
                        <option>Shift</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Format</label>
                    <select class="form-select">
                        <option>PDF (.pdf)</option>
                        <option>Excel (.xlsx)</option>
                        <option>CSV (.csv)</option>
                    </select>
                </div>
            </div>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500">
                <input type="checkbox" checked style="accent-color:var(--blue-600)">
                Include Visualization Charts
            </label>
            <button class="btn btn--primary w-full" style="justify-content:center; padding:11px">Generate Report</button>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Revenue trend chart
    const rev = document.getElementById('revenueChart');
    if (rev) {
        const labels = Array.from({length:31}, (_,i) => i+1);
        const data   = [1.8,2.1,2.4,3.1,2.8,3.5,2.9,3.2,2.7,3.0,2.4,4.2,3.8,2.9,3.1,2.6,3.4,3.7,2.8,3.0,2.5,3.8,4.0,3.2,2.9,3.5,3.1,2.7,3.6,3.3,2.8];
        new Chart(rev, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: data.map((v,i) => i===11 ? '#2563EB' : 'rgba(37,99,235,.2)'),
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` Rp ${c.parsed.y.toFixed(1)}M` } } },
                scales: {
                    x: { grid: { display: false }, ticks: { display: false } },
                    y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 }, color: '#94A3B8', callback: v => `${v}M` } }
                }
            }
        });
    }

    // Category donut chart
    const cat = document.getElementById('categoryChart');
    if (cat) {
        new Chart(cat, {
            type: 'doughnut',
            data: {
                labels: ['Beras & Sembako','Minyak & Lemak','Bumbu','Minuman'],
                datasets: [{
                    data: [45, 22, 20, 13],
                    backgroundColor: ['#2563EB','#F59E0B','#8B5CF6','#22C55E'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed}%` } }
                }
            }
        });
    }
});
</script>
@endpush
