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
    <button class="btn-icon" title="Scan barcode">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
            <line x1="7" y1="12" x2="7" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="17" y1="12" x2="17" y2="12"/>
        </svg>
    </button>
    <button class="btn-icon" title="Notifications">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
    </button>
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
            <div class="stat-card__value">4.892.500</div>
        </div>
        <div class="stat-card__meta">
            <span class="badge-trend badge-trend--up">↑ +12.5%</span>
            <span class="text-muted text-sm">vs yesterday</span>
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
        <div class="stat-card__value">12</div>
        <div class="stat-card__meta">
            <span class="status-dot status-dot--green"></span>
            <span class="text-sm" style="color:var(--green-700); font-weight:600">ACTIVE</span>
            <span class="text-muted text-sm">Members</span>
        </div>
    </div>

    {{-- Low Stock Items --}}
    <div class="stat-card" style="border-color: #FEF3C7; background:#FFFBEB">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#92400E">Low Stock Items</span>
            <div class="stat-card__icon" style="background:#FEF3C7">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value" style="color:#D97706">24</div>
        <div class="stat-card__meta">
            <span class="badge badge--red">8 Critical</span>
            <span class="text-sm" style="color:#92400E">needs restock</span>
        </div>
    </div>

    {{-- Top Selling Item --}}
    <div class="stat-card" style="background: var(--sidebar-bg); border-color: transparent">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#475569">Top Selling Item</span>
            <div class="stat-card__icon" style="background:rgba(37,99,235,.25)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#93C5FD" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
        </div>
        <div style="font-size:15px; font-weight:700; color:#fff; line-height:1.25">Organic Avocado<br>(Hass)</div>
        <div class="stat-card__meta" style="color:#64748B">
            <span style="color:#93C5FD; font-weight:600">420 units</span> sold today
        </div>
    </div>

</div>

{{-- ===== LIVE INVENTORY + TOP SELLERS ===== --}}
<div class="card-grid card-grid--60-40">

    {{-- Live Inventory Table --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Live Inventory Status</div>
            </div>
            <a href="{{ route('inventory.index') }}" class="card-action-link">
                View Full Report →
            </a>
        </div>
        <div class="data-table-wrapper" style="border: none; border-radius: 0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>SKU</th>
                        <th>Current Stock</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $items = [
                        ['name'=>'Heirloom Carrots',    'sku'=>'PROD-1029', 'stock'=>142,  'price'=>'Rp 3.990',  'status'=>'healthy'],
                        ['name'=>'Organic Avocado',     'sku'=>'PROD-4921', 'stock'=>12,   'price'=>'Rp 1.500',  'status'=>'low'],
                        ['name'=>'Sourdough Loaf',      'sku'=>'BAKE-0821', 'stock'=>28,   'price'=>'Rp 6.500',  'status'=>'healthy'],
                        ['name'=>'Sumatra Coffee Beans','sku'=>'BEV-9920',  'stock'=>0,    'price'=>'Rp 18.000', 'status'=>'out'],
                    ];
                    @endphp

                    @foreach($items as $item)
                    <tr>
                        <td class="product-name-cell">{{ $item['name'] }}</td>
                        <td class="table-id">{{ $item['sku'] }}</td>
                        <td>
                            <span style="font-weight:600; color:{{ $item['stock'] === 0 ? 'var(--red-500)' : ($item['stock'] < 15 ? 'var(--amber-700)' : 'var(--text-primary)') }}">
                                {{ $item['stock'] === 0 ? '0 Units' : $item['stock'].' Units' }}
                            </span>
                        </td>
                        <td class="text-secondary">{{ $item['price'] }}</td>
                        <td>
                            @if($item['status'] === 'healthy')
                                <span class="badge badge--green">Healthy</span>
                            @elseif($item['status'] === 'low')
                                <span class="badge badge--amber">Low Stock</span>
                            @else
                                <span class="badge badge--red">Out of Stock</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:4px">
                                <button class="btn-icon" title="Edit">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button class="btn-icon" title="Delete" style="color:var(--red-500)">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top 5 Best Sellers --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Top 5 Best Sellers</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
            @php
            $sellers = [
                ['rank'=>1, 'name'=>'Avocado Hass',      'sales'=>824, 'color'=>'var(--blue-600)'],
                ['rank'=>2, 'name'=>'Fresh Milk 1L',     'sales'=>650, 'color'=>'var(--blue-600)'],
                ['rank'=>3, 'name'=>'Artisan Bread',     'sales'=>412, 'color'=>'var(--blue-600)'],
                ['rank'=>4, 'name'=>'Local Eggs (12)',   'sales'=>388, 'color'=>'var(--blue-600)'],
                ['rank'=>5, 'name'=>'Red Apples',        'sales'=>310, 'color'=>'var(--blue-600)'],
            ];
            $max = 824;
            @endphp

            @foreach($sellers as $s)
            <div style="display:flex; align-items:center; gap:12px">
                <div style="width:26px; height:26px; border-radius:50%; background:{{ $s['rank']===1 ? 'var(--blue-600)' : 'var(--border-light)' }}; color:{{ $s['rank']===1 ? '#fff' : 'var(--text-secondary)' }}; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0">
                    {{ $s['rank'] }}
                </div>
                <div style="flex:1; min-width:0">
                    <div style="font-size:13px; font-weight:600; margin-bottom:4px">{{ $s['name'] }}</div>
                    <div class="progress-bar">
                        <div class="progress-bar__fill" style="width:{{ round($s['sales']/$max*100) }}%; background:var(--blue-600)"></div>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:700; color:var(--blue-600); flex-shrink:0">{{ number_format($s['sales']) }}</div>
            </div>
            @endforeach
        </div>

        {{-- Inventory Insight box --}}
        <div style="margin: 0 20px 20px; padding:14px 16px; background:var(--blue-50); border:1px solid var(--blue-100); border-radius:var(--radius); display:flex; gap:10px">
            <svg width="16" height="16" style="margin-top:1px; flex-shrink:0; color:var(--blue-600)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--blue-600); margin-bottom:4px">Inventory Insight</div>
                <div style="font-size:12.5px; color:var(--text-secondary); line-height:1.5">
                    Supply levels for <strong>Organic Produce</strong> are down by 15% compared to last Tuesday. Consider rescheduling the morning restock shift.
                </div>
                <button class="btn btn--secondary btn--sm" style="margin-top:10px">Adjust Shift Schedule</button>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
// Dashboard has no charts on the current Figma view — placeholder for future
</script>
@endpush
