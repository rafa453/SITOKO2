@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Analyze store performance across sales, inventory, and staff.')

@section('header-actions')
    <form method="GET" action="{{ route('reports.index') }}" style="display:flex; gap:8px; align-items:center">
        <select name="period" class="form-select" style="width:160px" onchange="this.form.submit()">
            <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
            <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last Month</option>
            <option value="last_7"     {{ $period === 'last_7'     ? 'selected' : '' }}>Last 7 Days</option>
            <option value="today"      {{ $period === 'today'      ? 'selected' : '' }}>Today</option>
        </select>
        <button type="submit" class="btn btn--secondary">Apply</button>
        <button type="button" class="btn btn--primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Export PDF
        </button>
    </form>
@endsection

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Revenue</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card__meta">
            <span class="badge-trend badge-trend--up">{{ $totalTrx }} transactions</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Transactions</span>
        </div>
        <div class="stat-card__value">{{ number_format($totalTrx) }}</div>
        <div class="stat-card__meta text-muted text-sm">
            @if($dailyRevenue->count() > 0)
                avg. {{ round($totalTrx / $dailyRevenue->count()) }} trx/day
            @else
                no data
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Best Selling Product</span>
        </div>
        @if($bestSeller)
            <div style="font-size:16px; font-weight:700; margin-top:4px; line-height:1.3">
                {{ $bestSeller->name }}
            </div>
            <div class="stat-card__meta">
                <span class="badge badge--blue">{{ strtoupper($bestSeller->category) }}</span>
                <span class="text-muted text-sm">{{ number_format($bestSeller->total_sold) }} units</span>
            </div>
        @else
            <div style="font-size:14px; color:var(--text-muted); margin-top:8px">No data</div>
        @endif
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Net Profit Estimate</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value" style="color:var(--green-700)">
                {{ number_format($netProfit, 0, ',', '.') }}
            </div>
        </div>
    </div>

</div>

{{-- ===== DAILY REVENUE TREND CHART ===== --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Daily Revenue Trend</div>
        </div>
    </div>
    <div class="card-body">
        @php
            $maxRevenue  = $dailyRevenue->max('revenue') ?? 0;
            $avgRevenue  = $dailyRevenue->avg('revenue') ?? 0;
            $maxDate     = $dailyRevenue->sortByDesc('revenue')->first();
        @endphp
        <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px">
            @if($maxDate)
                Highest: Rp {{ number_format($maxRevenue, 0, ',', '.') }}
                — {{ \Carbon\Carbon::parse($maxDate->date)->translatedFormat('d M') }}
                &nbsp;|&nbsp;
                Avg: Rp {{ number_format($avgRevenue, 0, ',', '.') }}/day
            @else
                No revenue data for this period.
            @endif
        </div>
        <div class="chart-wrapper" style="height:220px">
            <canvas id="revenueChart"></canvas>
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
            @forelse($topProducts as $i => $t)
            <div style="display:flex; align-items:center; gap:10px; padding:6px 0; border-bottom:1px solid var(--border-light)">
                <div style="width:22px; height:22px; border-radius:50%;
                            background:{{ $i === 0 ? 'var(--blue-600)' : 'var(--border-light)' }};
                            color:{{ $i === 0 ? '#fff' : 'var(--text-secondary)' }};
                            display:flex; align-items:center; justify-content:center;
                            font-size:10px; font-weight:700; flex-shrink:0">
                    {{ $i + 1 }}
                </div>
                <span style="flex:1; font-size:13px; font-weight:500">{{ $t->name }}</span>
                <span style="font-size:13px; font-weight:700; color:var(--blue-600)">
                    Rp {{ number_format($t->revenue, 0, ',', '.') }}
                </span>
            </div>
            @empty
                <p class="text-muted text-sm">No product data for this period.</p>
            @endforelse
        </div>
    </div>

    {{-- Revenue by Category --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Revenue by Category</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; align-items:center; gap:16px">
            <div class="chart-wrapper" style="height:160px; width:160px">
                <canvas id="categoryChart"></canvas>
            </div>
            @php
                $catColors      = ['#2563EB','#F59E0B','#8B5CF6','#22C55E','#EF4444','#0EA5E9','#EC4899'];
                $totalCatRev    = $categoryRevenue->sum('revenue') ?: 1;
            @endphp
            <div style="display:flex; flex-direction:column; gap:8px; width:100%">
                @forelse($categoryRevenue as $i => $c)
                <div style="display:flex; align-items:center; gap:8px; font-size:12.5px">
                    <span class="status-dot" style="background:{{ $catColors[$i % count($catColors)] }}"></span>
                    <span style="flex:1">{{ $c->category }}</span>
                    <span style="font-weight:600">
                        {{ round($c->revenue / $totalCatRev * 100) }}%
                    </span>
                </div>
                @empty
                    <p class="text-muted text-sm">No category data.</p>
                @endforelse
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
                $avatarColors = ['avatar--blue', 'avatar--green', 'avatar--amber'];
            @endphp
            @forelse($cashierPerformance as $i => $c)
            <div style="padding:12px; border:1px solid var(--border); border-radius:var(--radius); display:flex; align-items:center; gap:10px">
                <div class="avatar {{ $avatarColors[$i % 3] }}">
                    {{ strtoupper(substr($c->name, 0, 2)) }}
                </div>
                <div style="flex:1; min-width:0">
                    <div style="font-size:13px; font-weight:700">{{ $c->name }}</div>
                    <div style="font-size:11.5px; color:var(--green-700)">
                        {{ $c->trx_count }} transactions
                    </div>
                </div>
                <div style="text-align:right; flex-shrink:0">
                    <div style="font-size:12.5px; font-weight:700">
                        {{ number_format($c->trx_count) }}
                        <span class="text-muted" style="font-weight:400; font-size:11px">trx</span>
                    </div>
                    <div style="font-size:12px; color:var(--blue-600); font-weight:600">
                        Rp {{ number_format($c->revenue, 0, ',', '.') }}
                    </div>
                </div>
            </div>
            @empty
                <p class="text-muted text-sm">No cashier data for this period.</p>
            @endforelse
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
                    <tr>
                        <th>Product</th>
                        <th>Opening</th>
                        <th>Sold</th>
                        <th>Closing</th>
                        <th>Health</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoryMovement as $m)
                    @php
                        $healthColor = $m['closing'] == 0 ? 'red'
                            : ($m['closing'] <= ($m['opening'] * 0.2) ? 'amber' : 'green');
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ $m['name'] }}</td>
                        <td class="text-secondary">{{ number_format($m['opening']) }}</td>
                        <td style="font-weight:600; color:{{ $m['sold'] >= $m['opening'] * 0.8 ? 'var(--red-500)' : 'var(--text-primary)' }}">
                            {{ number_format($m['sold']) }}
                        </td>
                        <td style="font-weight:600">{{ number_format($m['closing']) }}</td>
                        <td><span class="status-dot status-dot--{{ $healthColor }}"></span></td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted)">
                                No movement data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Shift Summary</div>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Shift</th>
                        <th>Staff</th>
                        <th>TRX</th>
                        <th>Revenue</th>
                        <th>Avg/Staff</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shiftSummary as $s)
                    <tr>
                        <td style="font-weight:600">{{ ucfirst($s->shift) }}</td>
                        <td class="text-secondary">{{ $s->staff_count }}</td>
                        <td style="font-weight:600">{{ number_format($s->trx_count) }}</td>
                        <td style="font-weight:600; color:var(--blue-600)">
                            Rp {{ number_format($s->revenue, 0, ',', '.') }}
                        </td>
                        <td class="text-secondary">
                            Rp {{ $s->staff_count > 0 ? number_format($s->revenue / $s->staff_count, 0, ',', '.') : '0' }}
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted)">
                                No shift data for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ===== SCHEDULED REPORTS + CUSTOM BUILDER (hardcode) ===== --}}
<div class="card-grid card-grid--2">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg width="14" height="14" style="display:inline; margin-right:5px; vertical-align:middle"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Saved / Scheduled Reports
            </div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:8px">
            @php
            $saved = [
                ['icon'=>'📄','title'=>'Monthly Sales Summary',       'sub'=>'Scheduled: 1st of every month'],
                ['icon'=>'⚠️','title'=>'Low Stock Alert Report',      'sub'=>'Frequency: Daily at 08:00 AM'],
                ['icon'=>'💰','title'=>'Staff Incentive Calculation', 'sub'=>'Last Generated: 2 days ago'],
            ];
            @endphp
            @foreach($saved as $r)
            <div style="display:flex; align-items:center; gap:12px; padding:12px 14px;
                        border:1px solid var(--border); border-radius:var(--radius);
                        cursor:pointer; transition:background .15s"
                 onmouseover="this.style.background='var(--border-light)'"
                 onmouseout="this.style.background=''">
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
            <button class="btn btn--primary w-full" style="justify-content:center; padding:11px">
                Generate Report
            </button>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Revenue Trend Chart (data dari controller) ──
    const revCanvas = document.getElementById('revenueChart');
    if (revCanvas) {
        const rawDates    = @json($dailyRevenue->pluck('date'));
        const rawRevenues = @json($dailyRevenue->pluck('revenue'));
        const maxVal      = Math.max(...rawRevenues);

        new Chart(revCanvas, {
            type: 'bar',
            data: {
                labels: rawDates,
                datasets: [{
                    data: rawRevenues,
                    backgroundColor: rawRevenues.map(v => v === maxVal ? '#2563EB' : 'rgba(37,99,235,.2)'),
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: c => ` Rp ${Number(c.parsed.y).toLocaleString('id-ID')}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 10 },
                            color: '#94A3B8',
                            maxTicksLimit: 8,
                            callback: function(val, i) {
                                const d = new Date(rawDates[i]);
                                return d.toLocaleDateString('id-ID', { day:'2-digit', month:'short' });
                            }
                        }
                    },
                    y: {
                        grid: { color: '#F1F5F9' },
                        ticks: {
                            font: { size: 11 },
                            color: '#94A3B8',
                            callback: v => `Rp ${(v/1000000).toFixed(1)}M`
                        }
                    }
                }
            }
        });
    }

    // ── Category Donut Chart (data dari controller) ──
    const catCanvas = document.getElementById('categoryChart');
    if (catCanvas) {
        const catLabels = @json($categoryRevenue->pluck('category'));
        const catValues = @json($categoryRevenue->pluck('revenue'));
        const catColors = ['#2563EB','#F59E0B','#8B5CF6','#22C55E','#EF4444','#0EA5E9','#EC4899'];

        new Chart(catCanvas, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: catColors.slice(0, catLabels.length),
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
                    tooltip: {
                        callbacks: {
                            label: c => ` ${c.label}: Rp ${Number(c.parsed).toLocaleString('id-ID')}`
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush