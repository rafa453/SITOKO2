@extends('layouts.app')

@section('title', 'Inventory')
@section('page-title', 'Inventory')
@section('page-subtitle', 'Manage your stock, categories, and supplier data.')

@section('header-actions')
    <div class="filter-bar">
        <div class="search-input-wrapper" style="width:220px">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" class="form-input" placeholder="Search product...">
        </div>
        <select class="form-select" style="width:150px">
            <option>All Categories</option>
            <option>Beras & Sembako</option>
            <option>Minyak & Lemak</option>
            <option>Minuman</option>
            <option>Bumbu</option>
            <option>Snack</option>
        </select>
        <select class="form-select" style="width:140px">
            <option>Stock Status</option>
            <option>Healthy</option>
            <option>Low Stock</option>
            <option>Out of Stock</option>
        </select>
    </div>
    <a href="{{ route('inventory.create') }}" class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Product
    </a>
@endsection

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total SKUs</span>
            <div class="stat-card__icon" style="background:#EFF6FF">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value">124</div>
        <div class="stat-card__meta text-muted text-sm">Active Products</div>
    </div>

    <div class="stat-card" style="border-color:#FEF3C7; background:#FFFBEB">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#92400E">Low Stock</span>
            <div class="stat-card__icon" style="background:#FEF3C7">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value" style="color:#D97706">12</div>
        <div class="stat-card__meta">
            <span class="badge badge--red">CRITICAL</span>
            <span class="text-sm" style="color:#92400E">Items below threshold</span>
        </div>
    </div>

    <div class="stat-card" style="border-color:#FEE2E2; background:#FEF2F2">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#991B1B">Out of Stock</span>
            <div class="stat-card__icon" style="background:#FEE2E2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value" style="color:#EF4444">3</div>
        <div class="stat-card__meta">
            <span class="badge badge--red">RESTOCK NOW</span>
            <span class="text-sm" style="color:#991B1B">Immediate action required</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Stock Value</span>
            <div class="stat-card__icon" style="background:#F0FDF4">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">42.500.000</div>
        </div>
        <div class="stat-card__meta text-muted text-sm">Estimated asset value</div>
    </div>

</div>

{{-- ===== CATEGORY BREAKDOWN + STOCK ALERTS ===== --}}
<div class="card-grid card-grid--60-40">

    {{-- Category Breakdown --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Category Breakdown</div>
            <span class="card-subtitle">BY ITEM COUNT</span>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
            @php
            $categories = [
                ['name'=>'Beras & Sembako', 'count'=>45, 'color'=>'#2563EB'],
                ['name'=>'Minyak & Lemak',  'count'=>28, 'color'=>'#0EA5E9'],
                ['name'=>'Minuman',          'count'=>22, 'color'=>'#6366F1'],
                ['name'=>'Bumbu',            'count'=>15, 'color'=>'#8B5CF6'],
                ['name'=>'Snack',            'count'=>14, 'color'=>'#A78BFA'],
            ];
            $maxCount = 45;
            @endphp

            @foreach($categories as $cat)
            <div>
                <div style="display:flex; justify-content:space-between; margin-bottom:5px">
                    <span style="font-size:13px; font-weight:500">{{ $cat['name'] }}</span>
                    <span style="font-size:13px; color:var(--text-muted)">{{ $cat['count'] }} items</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar__fill" style="width:{{ round($cat['count']/$maxCount*100) }}%; background:{{ $cat['color'] }}"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Stock Alerts --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Stock Alerts</div>
            <span class="badge badge--red">5</span>
        </div>
        <div class="card-body" style="padding-top:8px; padding-bottom:8px">
            @php
            $alerts = [
                ['name'=>'Beras Premium 5kg',  'detail'=>'2 Bags Left',   'level'=>'critical'],
                ['name'=>'Minyak Goreng 1L',   'detail'=>'12 Pcs (Low)',  'level'=>'low'],
                ['name'=>'Garam Dapur 250g',   'detail'=>'Out of Stock',  'level'=>'out'],
                ['name'=>'Sabun Cuci Piring',  'detail'=>'8 Pcs (Low)',   'level'=>'low'],
                ['name'=>'Mie Instan Kari',    'detail'=>'15 Pcs (Low)',  'level'=>'low'],
            ];
            @endphp

            @foreach($alerts as $alert)
            <div class="alert-item">
                <div class="alert-item__info">
                    <div class="alert-item__name">{{ $alert['name'] }}</div>
                    <div class="alert-item__detail {{ $alert['level']==='out' ? 'text-danger' : ($alert['level']==='critical' ? 'text-danger' : 'text-warning') }}">
                        @if($alert['level']==='out')
                            <span class="status-dot status-dot--red" style="margin-right:4px"></span>
                        @elseif($alert['level']==='critical')
                            <span style="color:var(--red-500)">↓ </span>
                        @else
                            <span style="color:var(--amber-500)">⚠ </span>
                        @endif
                        {{ $alert['detail'] }}
                    </div>
                </div>
                <button class="btn btn--secondary btn--sm">Restock</button>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ===== PRODUCT INVENTORY TABLE ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Product Inventory</div>
        <div style="display:flex; gap:8px">
            <button class="btn-icon" title="Filter">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </button>
            <button class="btn-icon" title="Export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Threshold</th>
                    <th>Buy Price</th>
                    <th>Sell Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                $products = [
                    ['id'=>'PRD-0042','name'=>'Beras Premium 5kg','tag'=>'STAPLE',  'cat'=>'Beras & Sembako','unit'=>'Bag', 'qty'=>2,  'threshold'=>18,'buy'=>'Rp 62.000','sell'=>'Rp 68.500','low'=>true],
                    ['id'=>'PRD-0043','name'=>'Minyak Goreng 1L', 'tag'=>'LIQUID',  'cat'=>'Minyak & Lemak', 'unit'=>'Pcs', 'qty'=>12, 'threshold'=>24,'buy'=>'Rp 14.200','sell'=>'Rp 16.000','low'=>true],
                    ['id'=>'PRD-0044','name'=>'Gula Pasir 1kg',   'tag'=>'STAPLE',  'cat'=>'Beras & Sembako','unit'=>'Kg',  'qty'=>48, 'threshold'=>15,'buy'=>'Rp 13.500','sell'=>'Rp 15.000','low'=>false],
                    ['id'=>'PRD-0045','name'=>'Teh Celup 25s',    'tag'=>'DRINK',   'cat'=>'Minuman',        'unit'=>'Box', 'qty'=>62, 'threshold'=>10,'buy'=>'Rp 5.200', 'sell'=>'Rp 6.500', 'low'=>false],
                    ['id'=>'PRD-0046','name'=>'Kecap Manis 600ml','tag'=>'CONDIMENT','cat'=>'Bumbu',         'unit'=>'Pouch','qty'=>25,'threshold'=>5, 'buy'=>'Rp 18.000','sell'=>'Rp 21.000','low'=>false],
                ];
                @endphp

                @foreach($products as $p)
                <tr>
                    <td class="table-id">{{ $p['id'] }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px">{{ $p['name'] }}</div>
                        <span class="badge badge--{{ $p['tag']==='STAPLE' ? 'blue' : ($p['tag']==='LIQUID' ? 'purple' : ($p['tag']==='DRINK' ? 'green' : 'gray')) }}" style="margin-top:3px">
                            {{ $p['tag'] }}
                        </span>
                    </td>
                    <td class="text-secondary">{{ $p['cat'] }}</td>
                    <td class="text-secondary">{{ $p['unit'] }}</td>
                    <td>
                        <span style="font-weight:700; color:{{ $p['qty'] <= $p['threshold'] ? 'var(--red-500)' : 'var(--text-primary)' }}">
                            {{ $p['qty'] }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $p['threshold'] }}</td>
                    <td class="text-secondary">{{ $p['buy'] }}</td>
                    <td style="font-weight:600">{{ $p['sell'] }}</td>
                    <td>
                        <div style="display:flex; gap:4px">
                            <button class="btn-icon" title="Edit">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-icon" title="Quick restock">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 8M17 13l1.5 8M9 21h6"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span class="pagination-info">Showing 1–8 of 124 products</span>
        <div class="pagination-controls">
            <button class="page-btn" disabled>‹</button>
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
            <button class="page-btn">›</button>
        </div>
    </div>
</div>

{{-- ===== SUPPLIER QUICK REFERENCE ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Supplier Quick Reference</div>
        <a href="#" class="card-action-link">View All Suppliers →</a>
    </div>
    <div class="card-body">
        <div class="card-grid card-grid--3">
            @php
            $suppliers = [
                ['initials'=>'SJ','name'=>'Sembako Jaya',   'desc'=>'Rice, Flour, Staple Goods',  'phone'=>'+62 21 555-0123','last'=>'Oct 24, 2023','color'=>'var(--blue-600)'],
                ['initials'=>'SM','name'=>'Sumber Makmur',  'desc'=>'Cooking Oil, Margarine',      'phone'=>'+62 21 555-0987','last'=>'Oct 21, 2023','color'=>'var(--green-500)'],
                ['initials'=>'BP','name'=>'Bumbu Pusaka',   'desc'=>'Spices, Condiments',          'phone'=>'+62 21 555-0456','last'=>'Oct 18, 2023','color'=>'var(--amber-500)'],
            ];
            @endphp

            @foreach($suppliers as $s)
            <div style="border:1px solid var(--border); border-radius:var(--radius); padding:16px; display:flex; flex-direction:column; gap:10px">
                <div style="display:flex; align-items:center; gap:10px">
                    <div class="avatar" style="background:{{ $s['color'] }}; width:38px; height:38px; font-size:13px">{{ $s['initials'] }}</div>
                    <div>
                        <div style="font-size:13.5px; font-weight:700">{{ $s['name'] }}</div>
                        <div style="font-size:12px; color:var(--text-muted)">{{ $s['desc'] }}</div>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:4px; font-size:12.5px; color:var(--text-secondary)">
                    <div style="display:flex; align-items:center; gap:6px">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.56 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        {{ $s['phone'] }}
                    </div>
                    <div style="display:flex; align-items:center; gap:6px">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Last Delivery: {{ $s['last'] }}
                    </div>
                </div>
                <button class="btn btn--secondary btn--sm w-full" style="justify-content:center">Contact Supplier</button>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
