@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Live store overview and key metrics.')

@section('header-actions')
    <div class="search-input-wrapper" style="width: 220px">
        <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" class="form-input" placeholder="Search analytics...">
    </div>
@endsection

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    {{-- Today's Revenue --}}
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Today's Revenue</span>
            <div class="stat-card__icon" style="background:#EFF6FF">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">{{ number_format($todayRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card__meta">
            <span class="text-muted text-sm">transactions today</span>
        </div>
    </div>

    {{-- On-Shift Staff --}}
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">On-Shift Staff</span>
            <div class="stat-card__icon" style="background:#F0FDF4">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value">{{ $onDutyStaff }}</div>
        <div class="stat-card__meta">
            <span class="status-dot status-dot--green"></span>
            <span class="text-sm" style="color:var(--green-700); font-weight:600">ACTIVE</span>
            <span class="text-muted text-sm">Members</span>
        </div>
    </div>

    {{-- Low Stock Items --}}
    <div class="stat-card" style="border-color:#FEF3C7; background:#FFFBEB">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#92400E">Low Stock Items</span>
            <div class="stat-card__icon" style="background:#FEF3C7">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value" style="color:#D97706">{{ $lowStockCount }}</div>
        <div class="stat-card__meta">
            <span class="badge badge--red">{{ $outOfStockCount }} Out of Stock</span>
            <span class="text-sm" style="color:#92400E">needs restock</span>
        </div>
    </div>

    {{-- Top Selling Item --}}
    <div class="stat-card" style="background:var(--sidebar-bg); border-color:transparent">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#475569">Top Selling Item Today</span>
            <div class="stat-card__icon" style="background:rgba(37,99,235,.25)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#93C5FD" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
        </div>
        @if($topProducts->isNotEmpty())
            @php $top = $topProducts->first(); @endphp
            <div style="font-size:15px; font-weight:700; color:#fff; line-height:1.25">
                {{ $top->name }}
            </div>
            <div class="stat-card__meta" style="color:#64748B">
                <span style="color:#93C5FD; font-weight:600">
                    {{ number_format($top->revenue, 0, ',', '.') }}
                </span> revenue today
            </div>
        @else
            <div style="font-size:14px; color:#64748B; margin-top:8px">No sales today</div>
        @endif
    </div>

</div>

{{-- ===== LIVE INVENTORY + TOP SELLERS ===== --}}
<div class="card-grid card-grid--60-40">

    {{-- Live Inventory Table --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Live Inventory Status</div>
            <a href="{{ route('inventory.index') }}" class="card-action-link">View Full Report →</a>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>SKU</th>
                        <th>Current Stock</th>
                        <th>Sell Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($liveInventory as $item)
                    <tr>
                        <td class="product-name-cell" style="font-weight:600">{{ $item->name }}</td>
                        <td class="table-id">{{ $item->sku }}</td>
                        <td>
                            <span style="font-weight:600; color:{{
                                $item->qty == 0 ? 'var(--red-500)'
                                : ($item->qty <= $item->threshold ? 'var(--amber-700)' : 'var(--text-primary)')
                            }}">
                                {{ $item->qty }} {{ $item->unit }}
                            </span>
                        </td>
                        <td class="text-secondary">Rp {{ number_format($item->sell_price, 0, ',', '.') }}</td>
                        <td>
                            @if($item->qty == 0)
                                <span class="badge badge--red">Out of Stock</span>
                            @elseif($item->qty <= $item->threshold)
                                <span class="badge badge--amber">Low Stock</span>
                            @else
                                <span class="badge badge--green">Healthy</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:4px">
                                <a href="{{ route('inventory.edit', $item->id) }}" class="btn-icon" title="Edit">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted)">
                                No inventory data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top 5 Best Sellers --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Top Sellers — This Month</div>
            <a href="{{ route('reports.index') }}" class="card-action-link">View Reports →</a>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
            @php
                $maxSales = $topProducts->max('revenue') ?: 1;
            @endphp

            @forelse($topProducts as $i => $s)
            <div style="display:flex; align-items:center; gap:12px">
                <div style="width:26px; height:26px; border-radius:50%;
                            background:{{ $i === 0 ? 'var(--blue-600)' : 'var(--border-light)' }};
                            color:{{ $i === 0 ? '#fff' : 'var(--text-secondary)' }};
                            display:flex; align-items:center; justify-content:center;
                            font-size:11px; font-weight:700; flex-shrink:0">
                    {{ $i + 1 }}
                </div>
                <div style="flex:1; min-width:0">
                    <div style="font-size:13px; font-weight:600; margin-bottom:4px">{{ $s->name }}</div>
                    <div class="progress-bar">
                        <div class="progress-bar__fill"
                             style="width:{{ round($s->revenue / $maxSales * 100) }}%; background:var(--blue-600)">
                        </div>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:700; color:var(--blue-600); flex-shrink:0">
                    Rp {{ number_format($s->revenue, 0, ',', '.') }}
                </div>
            </div>
            @empty
                <p class="text-muted text-sm">No sales data this month.</p>
            @endforelse
        </div>

        {{-- Low Stock Alert box --}}
        @if($lowStockCount > 0)
        <div style="margin:0 20px 20px; padding:14px 16px; background:#FFFBEB;
                    border:1px solid #FEF3C7; border-radius:var(--radius); display:flex; gap:10px">
            <svg width="16" height="16" style="margin-top:1px; flex-shrink:0; color:#F59E0B"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase;
                            letter-spacing:.5px; color:#D97706; margin-bottom:4px">
                    Stock Alert
                </div>
                <div style="font-size:12.5px; color:var(--text-secondary); line-height:1.5">
                    <strong>{{ $lowStockCount }}</strong> item{{ $lowStockCount > 1 ? 's' : '' }} below threshold,
                    <strong>{{ $outOfStockCount }}</strong> completely out of stock.
                </div>
                <a href="{{ route('inventory.index') }}?status=low"
                   class="btn btn--secondary btn--sm" style="margin-top:10px; display:inline-flex">
                    View Stock Alerts
                </a>
            </div>
        </div>
        @endif
    </div>

</div>

@endsection