@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions')
@section('page-subtitle', 'Monitor, search, and review all sales transactions.')

@section('header-actions')
    <div class="filter-bar">
        <div class="search-input-wrapper" style="width:200px">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" class="form-input" placeholder="Search...">
        </div>
        <button class="btn btn--secondary btn--sm" style="font-size:12.5px">Today</button>
        <button class="btn btn--secondary btn--sm" style="font-size:12.5px">Methods</button>
        <button class="btn btn--secondary btn--sm" style="font-size:12.5px">Status</button>
    </div>
    <button class="btn btn--primary btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export CSV
    </button>
@endsection

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Transactions</span>
            <span class="badge-trend badge-trend--up">↑ +12%</span>
        </div>
        <div class="stat-card__value">1,248</div>
        <div class="stat-card__meta text-muted text-sm">Since 07:00 AM</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Revenue</span>
            <span class="badge-trend badge-trend--up">↑ +8.4%</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">42.8M</div>
        </div>
        <div class="stat-card__meta text-muted text-sm">Real-time daily gross</div>
    </div>

    <div class="stat-card" style="border-color:#FEE2E2; background:#FEF2F2">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#991B1B">Voided</span>
            <span class="badge badge--red">Action Req.</span>
        </div>
        <div class="stat-card__value" style="color:#EF4444">14</div>
        <div class="stat-card__meta text-sm" style="color:#991B1B">Value: Rp 2.450.000</div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Avg. Basket Size</span>
            <span class="text-muted text-sm">— Steady</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">34.3k</div>
        </div>
        <div class="stat-card__meta text-muted text-sm">vs Weekly Avg: Rp 32.1k</div>
    </div>

</div>

{{-- ===== CHART + PAYMENT BREAKDOWN ===== --}}
<div class="card-grid card-grid--60-40">

    {{-- Hourly Volume Chart --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Hourly Transaction Volume</div>
                <div class="card-subtitle">Daily activity patterns (07.00 – 21.00)</div>
            </div>
            <span class="badge badge--blue" style="font-size:11px">
                ☆ Peak: 12.00 (38 trx)
            </span>
        </div>
        <div class="card-body">
            <div class="chart-wrapper" style="height:180px">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Payment Breakdown --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Payment Breakdown</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
            @php
            $methods = [
                ['label'=>'E-Wallet', 'trx'=>642,  'amount'=>'Rp 22.4M', 'pct'=>54, 'color'=>'#2563EB'],
                ['label'=>'Debit Card','trx'=>410,  'amount'=>'Rp 15.2M', 'pct'=>35, 'color'=>'#6366F1'],
                ['label'=>'Cash',      'trx'=>196,  'amount'=>'Rp 5.2M',  'pct'=>11, 'color'=>'#22C55E'],
            ];
            @endphp

            @foreach($methods as $m)
            <div>
                <div style="display:flex; justify-content:space-between; margin-bottom:5px; align-items:center">
                    <div style="display:flex; align-items:center; gap:8px">
                        <span class="status-dot" style="background:{{ $m['color'] }}"></span>
                        <span style="font-weight:600; font-size:13px">{{ $m['label'] }}</span>
                        <span class="text-muted text-sm">{{ $m['trx'] }}</span>
                    </div>
                    <span style="font-weight:700; font-size:13px">{{ $m['amount'] }}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar__fill" style="width:{{ $m['pct'] }}%; background:{{ $m['color'] }}"></div>
                </div>
            </div>
            @endforeach

            {{-- Conversion Rate --}}
            <div style="margin-top:8px; background:var(--border-light); border-radius:var(--radius); padding:14px 16px; display:flex; align-items:center; justify-content:space-between">
                <span style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted)">Conversion Rate</span>
                <span style="font-size:22px; font-weight:800; color:var(--blue-600)">94.2%</span>
            </div>
        </div>
    </div>

</div>

{{-- ===== TRANSACTION LOG + DETAIL PANEL ===== --}}
<div class="card-grid" style="grid-template-columns: 1fr 380px" x-data="{ selected: 'TRX-20240521-0042' }">

    {{-- Detailed Log --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Detailed Log</div>
            <div style="display:flex; gap:6px">
                <button class="btn-icon" title="Filter">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                </button>
                <button class="btn-icon" title="Refresh">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Timestamp</th>
                        <th>Cashier</th>
                        <th>Shift</th>
                        <th>Items</th>
                        <th>Method</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $transactions = [
                        ['id'=>'TRX-20240521-0042','time'=>'21/05 14:32:10','cashier'=>'BA','name'=>'Budi A.','shift'=>'Morning','items'=>8, 'method'=>'QRIS',  'total'=>'Rp 435.500',   'status'=>'completed'],
                        ['id'=>'TRX-20240521-0041','time'=>'21/05 14:28:45','cashier'=>'LS','name'=>'Lestari S.','shift'=>'Morning','items'=>2,'method'=>'CASH', 'total'=>'Rp 25.000',    'status'=>'completed'],
                        ['id'=>'TRX-20240521-0040','time'=>'21/05 14:15:22','cashier'=>'BA','name'=>'Budi A.','shift'=>'Morning','items'=>15,'method'=>'DEBIT', 'total'=>'Rp 1.250.000', 'status'=>'completed'],
                        ['id'=>'TRX-20240521-0039','time'=>'21/05 14:02:11','cashier'=>'DS','name'=>'Dewi S.','shift'=>'Morning','items'=>4, 'method'=>'G-PAY', 'total'=>'Rp 88.200',    'status'=>'completed'],
                        ['id'=>'TRX-20240521-0038','time'=>'21/05 13:58:30','cashier'=>'BA','name'=>'Budi A.','shift'=>'Morning','items'=>1, 'method'=>'CASH',  'total'=>'Rp 12.500',    'status'=>'voided'],
                    ];
                    @endphp

                    @foreach($transactions as $t)
                    <tr style="cursor:pointer" @click="selected = '{{ $t['id'] }}'">
                        <td>
                            <span style="font-weight:600; font-size:12.5px; color:var(--blue-600)">{{ $t['id'] }}</span>
                        </td>
                        <td class="table-id">{{ $t['time'] }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:7px">
                                <div class="avatar avatar--blue" style="width:26px; height:26px; font-size:10px">{{ $t['cashier'] }}</div>
                                <span style="font-size:12.5px">{{ $t['name'] }}</span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $t['shift'] }}</td>
                        <td class="text-secondary">{{ $t['items'] }} items</td>
                        <td>
                            <span class="badge {{ $t['method']==='CASH' ? 'badge--green' : ($t['method']==='QRIS' ? 'badge--blue' : ($t['method']==='DEBIT' ? 'badge--purple' : 'badge--amber')) }}">
                                {{ $t['method'] }}
                            </span>
                        </td>
                        <td style="font-weight:600">{{ $t['total'] }}</td>
                        <td>
                            @if($t['status']==='completed')
                                <span class="badge badge--green">COMPLETED</span>
                            @else
                                <span class="badge badge--red">VOIDED</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn-icon" style="font-size:11px; width:24px; height:24px">›</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <span class="pagination-info">Showing 1 to 10 of 1,248 entries</span>
            <div class="pagination-controls">
                <button class="page-btn" disabled>‹</button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <span class="page-btn" style="border:none; background:none">...</span>
                <button class="page-btn">125</button>
                <button class="page-btn">›</button>
            </div>
        </div>
    </div>

    {{-- Transaction Detail Panel --}}
    <div class="card" style="align-self:start; position:sticky; top:90px">
        <div class="card-header" style="padding:14px 18px">
            <div>
                <span class="badge badge--green" style="margin-bottom:6px; display:inline-flex">COMPLETED</span>
                <div style="font-size:14px; font-weight:700; font-family:var(--font-mono); letter-spacing:-.5px">TRX-20240521-0042</div>
            </div>
            <button class="btn-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Meta --}}
        <div style="padding:12px 18px; border-bottom:1px solid var(--border-light); display:grid; grid-template-columns:1fr 1fr; gap:12px">
            <div>
                <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:3px">Date & Time</div>
                <div style="font-size:12.5px; font-weight:600">May 21, 2024 • 14:32:18</div>
            </div>
            <div>
                <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:3px">Cashier</div>
                <div style="font-size:12.5px; font-weight:600">Budi Ardiansyah (Morning)</div>
            </div>
        </div>

        {{-- Itemized Receipt --}}
        <div style="padding:14px 18px">
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:10px">Itemized Receipt</div>

            <div style="display:grid; grid-template-columns:1fr auto auto auto; gap:4px 12px; font-size:12px; margin-bottom:4px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.4px">
                <span>Item</span><span>Qty</span><span>Price</span><span>Subtotal</span>
            </div>

            @php
            $items = [
                ['name'=>'Premium Arabica Beans 500g','qty'=>1,'price'=>'185.000','sub'=>'185.000'],
                ['name'=>'Organic Whole Milk 1L',      'qty'=>2,'price'=>'32.000', 'sub'=>'64.000'],
                ['name'=>'Avocado Butter (Local)',      'qty'=>3,'price'=>'45.000', 'sub'=>'135.000'],
                ['name'=>'Cinnamon Sticks Pack',        'qty'=>1,'price'=>'22.500', 'sub'=>'22.500'],
                ['name'=>'Reusable Eco Bag (M)',        'qty'=>1,'price'=>'29.000', 'sub'=>'29.000'],
            ];
            @endphp

            @foreach($items as $item)
            <div style="display:grid; grid-template-columns:1fr auto auto auto; gap:4px 12px; font-size:12.5px; padding:7px 0; border-bottom:1px solid var(--border-light); align-items:center">
                <span style="font-weight:500">{{ $item['name'] }}</span>
                <span class="text-muted">{{ $item['qty'] }}</span>
                <span class="text-secondary">{{ $item['price'] }}</span>
                <span style="font-weight:600">{{ $item['sub'] }}</span>
            </div>
            @endforeach

            {{-- Totals --}}
            <div style="margin-top:12px; display:flex; flex-direction:column; gap:6px; font-size:12.5px">
                <div style="display:flex; justify-content:space-between; color:var(--text-secondary)">
                    <span>Subtotal</span><span>435.500</span>
                </div>
                <div style="display:flex; justify-content:space-between; color:var(--text-secondary)">
                    <span>Tax (11% PPN)</span><span>0.00 (Incl.)</span>
                </div>
                <div class="divider"></div>
                <div style="display:flex; justify-content:space-between; font-weight:800; font-size:14px">
                    <span>Total Amount</span>
                    <span style="color:var(--blue-600)">Rp 435.500</span>
                </div>
            </div>

            {{-- Payment method --}}
            <div style="margin-top:12px; padding:10px 12px; background:var(--border-light); border-radius:var(--radius-sm); display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:600">
                <span class="badge badge--blue">QRIS</span>
                <span>Paid via QRIS</span>
                <span class="text-muted" style="margin-left:auto; font-size:11px">Ref: ID-8821992</span>
            </div>
        </div>

        {{-- Actions --}}
        <div style="padding:12px 18px 16px; display:flex; gap:8px; border-top:1px solid var(--border-light)">
            <button class="btn btn--secondary" style="flex:1; justify-content:center; font-size:12.5px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print Receipt
            </button>
            <button class="btn btn--danger" style="flex:0 0 auto; font-size:12.5px">
                Void
            </button>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('hourlyChart');
    if (!ctx) return;

    const hours = ['07','08','09','10','11','12','13','14','15','16','17','18','19','20','21'];
    const data  = [12, 18, 22, 25, 31, 38, 29, 35, 27, 24, 20, 18, 15, 11, 8];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: hours,
            datasets: [{
                data,
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37,99,235,.08)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#2563EB',
                pointHoverRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => ` ${ctx.parsed.y} trx` }
            }},
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94A3B8' } },
                y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 }, color: '#94A3B8' } }
            }
        }
    });
});
</script>
@endpush
