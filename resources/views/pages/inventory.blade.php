@extends('layouts.app')

@section('title', 'Inventory')
@section('page-title', 'Inventory')
@section('page-subtitle', 'Manage your stock, categories, and supplier data.')

@section('header-actions')
    @if(auth()->user()->role === 'admin')
    <a href="{{ route('inventory.create') }}" class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Product
    </a>
    @endif
@endsection

@push('styles')
<style>
.stat-card--clickable {
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}
.stat-card--clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
}
.stat-card--clickable:active {
    transform: translateY(0);
}

/* ── Inventory Detail Modal ── */
.inv-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.inv-modal-overlay.is-open {
    display: flex;
}
.inv-modal {
    background: #fff;
    border-radius: var(--radius, 10px);
    width: 100%;
    max-width: 680px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    animation: inv-modal-in .18s ease;
}
@keyframes inv-modal-in {
    from { opacity: 0; transform: translateY(8px) scale(.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.inv-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-light, #F1F5F9);
}
.inv-modal__title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary, #0F172A);
}
.inv-modal__close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: var(--border-light, #F1F5F9);
    color: var(--text-secondary, #475569);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .15s ease;
}
.inv-modal__close:hover {
    background: #E2E8F0;
}
.inv-modal__body {
    padding: 18px 20px;
    overflow-y: auto;
}
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert--error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid">

    <div class="stat-card stat-card--clickable" onclick="openInvModal('productInventoryCard', 'Product Inventory')">
        <div class="stat-card__header">
            <span class="stat-card__label">Total SKUs</span>
            <div class="stat-card__icon" style="background:#EFF6FF">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value">{{ number_format($totalSkus) }}</div>
        <div class="stat-card__meta text-muted text-sm">Active Products</div>
    </div>

    <div class="stat-card stat-card--clickable" style="border-color:#FEF3C7; background:#FFFBEB" onclick="openInvModal('stockAlertsCard', 'Stock Alerts')">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#92400E">Low Stock</span>
            <div class="stat-card__icon" style="background:#FEF3C7">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value" style="color:#D97706">{{ $lowStockCount }}</div>
        <div class="stat-card__meta">
            <span class="badge badge--red">CRITICAL</span>
            <span class="text-sm" style="color:#92400E">Items below threshold</span>
        </div>
    </div>

    <div class="stat-card stat-card--clickable" style="border-color:#FEE2E2; background:#FEF2F2" onclick="openInvModal('expiringProductsCard', 'Stock Alert — Mendekati Kadaluarsa')">
        <div class="stat-card__header">
            <span class="stat-card__label" style="color:#991B1B">Stock Alert</span>
            <div class="stat-card__icon" style="background:#FEE2E2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="stat-card__value" style="color:#EF4444">{{ $expiringCount }}</div>
        <div class="stat-card__meta">
            <span class="badge badge--red">EXPIRY</span>
            <span class="text-sm" style="color:#991B1B">Mendekati / sudah kadaluarsa</span>
        </div>
    </div>

    @if(auth()->user()->role === 'admin')
    <div class="stat-card stat-card--clickable" onclick="openInvModal('stockValueByCategoryCard', 'Stock Value by Category')">
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
            <div class="stat-card__value">{{ number_format($stockValue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card__meta text-muted text-sm">Estimated asset value</div>
    </div>
    @endif

</div>

{{-- ===== CATEGORY BREAKDOWN + STOCK ALERTS ===== --}}
<div class="card-grid card-grid--60-40">

    <div class="card">
        <div class="card-header">
            <div class="card-title">Category Breakdown</div>
            <span class="card-subtitle">BY ITEM COUNT</span>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
            @php
                $catColors = ['#2563EB','#0EA5E9','#6366F1','#8B5CF6','#A78BFA','#EC4899','#14B8A6'];
                $maxCount  = $categoryBreakdown->max('count') ?: 1;
            @endphp

            @foreach($categoryBreakdown as $i => $cat)
            <div>
                <div style="display:flex; justify-content:space-between; margin-bottom:5px">
                    <span style="font-size:13px; font-weight:500">{{ $cat->category ?? 'Uncategorized' }}</span>
                    <span style="font-size:13px; color:var(--text-muted)">{{ $cat->count }} items</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar__fill"
                         style="width:{{ round($cat->count / $maxCount * 100) }}%;
                                background:{{ $catColors[$i % count($catColors)] }}">
                    </div>
                </div>
            </div>
            @endforeach

            @if($categoryBreakdown->isEmpty())
                <p class="text-muted text-sm">No category data available.</p>
            @endif
        </div>
    </div>

    <div class="card" id="stockAlertsCard">
        <div class="card-header">
            <div class="card-title">Stock Alerts</div>
            <span class="badge badge--red">{{ $stockAlerts->count() }}</span>
        </div>
        <div class="card-body" style="padding-top:8px; padding-bottom:8px">
            @forelse($stockAlerts as $alert)
            <div class="alert-item">
                <div class="alert-item__info">
                    <div class="alert-item__name">{{ $alert->name }}</div>
                    <div class="alert-item__detail {{ $alert->qty == 0 ? 'text-danger' : 'text-warning' }}">
                        @if($alert->qty == 0)
                            <span class="status-dot status-dot--red" style="margin-right:4px"></span>
                            Out of Stock
                        @else
                            <span style="color:var(--amber-500)">⚠ </span>
                            {{ $alert->qty }} {{ $alert->unit }} Left (threshold: {{ $alert->threshold }})
                        @endif
                    </div>
                </div>
                @if(auth()->user()->role === 'admin')
                <form method="POST" action="{{ route('inventory.restock', $alert->id) }}" style="display:flex; gap:4px; align-items:center">
                    @csrf
                    <input type="number" name="qty" value="10" min="1"
                           style="width:52px; padding:4px 6px; font-size:12px; border:1px solid var(--border); border-radius:var(--radius-sm)">
                    <button type="submit" class="btn btn--secondary btn--sm">Restock</button>
                </form>
                @endif
            </div>
            @empty
                <p class="text-muted text-sm" style="padding:8px 0">No stock alerts. All items are healthy.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- Stock Value by Category (hidden, sumber popup) --}}
<div id="stockValueByCategoryCard" style="display:none">
    <div class="card-body" style="display:flex; flex-direction:column; gap:14px; padding:0">
        @php $svMax = $stockValueByCategory->max('value') ?: 1; @endphp
        @foreach($stockValueByCategory as $i => $cat)
        <div>
            <div style="display:flex; justify-content:space-between; margin-bottom:5px">
                <span style="font-size:13px; font-weight:500">{{ $cat->category ?? 'Uncategorized' }}</span>
                <span style="font-size:13px; color:var(--text-muted)">Rp {{ number_format($cat->value, 0, ',', '.') }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-bar__fill"
                     style="width:{{ round($cat->value / $svMax * 100) }}%;
                            background:{{ $catColors[$i % count($catColors)] }}">
                </div>
            </div>
        </div>
        @endforeach
        @if($stockValueByCategory->isEmpty())
            <p class="text-muted text-sm">No data available.</p>
        @endif
    </div>
</div>

{{-- Expiring Products (hidden, sumber popup Stock Alert) --}}
<div id="expiringProductsCard" style="display:none">
    <div class="data-table-wrapper" style="border:none; border-radius:0; padding:0">
        <table class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Sisa Stok</th>
                    <th>Tanggal Expired</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expiringProducts as $ep)
                @php
                    $isExpired = $ep->expired_at->isPast();
                    $daysLeft  = now()->diffInDays($ep->expired_at, false);
                @endphp
                <tr>
                    <td class="table-id">{{ $ep->sku }}</td>
                    <td style="font-weight:600; font-size:13px">{{ $ep->name }}</td>
                    <td class="text-secondary">{{ $ep->qty }} {{ $ep->unit }}</td>
                    <td class="text-secondary">{{ $ep->expired_at->format('d M Y') }}</td>
                    <td>
                        @if($isExpired)
                            <span class="badge badge--red">Sudah Expired</span>
                        @else
                            <span class="badge badge--yellow">H-{{ $daysLeft }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted)">
                        Tidak ada produk yang mendekati kadaluarsa.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== PRODUCT INVENTORY TABLE ===== --}}
<div class="card" id="productInventoryCard" style="overflow: visible;">
    <div class="card-header">
        <div class="card-title">Product Inventory</div>
        
        <form method="GET" action="{{ route('inventory.index') }}" id="filterForm" style="display:flex; align-items:center; gap:8px;">
            <div class="search-input-wrapper" style="width:250px">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" name="search" class="form-input w-full" placeholder="Search product..."
                    value="{{ request('search') }}"
                    onchange="this.form.submit()">
            </div>
            <button type="submit" style="display:none"></button>

            <div style="position: relative;" x-data="{ showFilters: false }">
                <button type="button" class="btn-icon" title="Filter" @click="showFilters = !showFilters" :style="showFilters ? 'background:var(--border-light); color:var(--blue-600)' : ''">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </button>

            {{-- Floating Filter Popover --}}
            <div x-show="showFilters" 
                 @click.away="showFilters = false" 
                 style="display:none; position:absolute; top:100%; right:0; z-index:99; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:0 10px 25px rgba(15,23,42,0.15); padding:16px; width:260px; margin-top:8px;">
            
            <div style="font-size:13px; font-weight:700; margin-bottom:12px; color:var(--text-primary); border-bottom:1px solid var(--border-light); padding-bottom:8px;">
                Filter Products
            </div>
            
            <div style="display:flex; flex-direction:column; gap:10px">
                <select name="category" form="filterForm" class="form-select w-full" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                            {{ $categoryLabels[$cat] ?? $cat }}
                        </option>
                    @endforeach
                </select>

                <select name="brand_id" form="filterForm" class="form-select w-full" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Brands</option>
                    @foreach($filterBrands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>

                <select name="supplier_id" form="filterForm" class="form-select w-full" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Suppliers</option>
                    @foreach($filterSuppliers as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" form="filterForm" class="form-select w-full" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Stock Status</option>
                    <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>
        </div>
        </div>
        </form>
    </div>

    <div class="data-table-wrapper" style="border:none; border-radius:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Threshold</th>
                    @if(auth()->user()->role === 'admin')
                        <th>Buy Price</th>
                    @endif
                    <th>Sell Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                <tr>
                    <td class="table-id">{{ $p->sku }}</td>
                    <td><div style="font-weight:600; font-size:13px">{{ $p->name }}</div></td>
                    <td class="text-secondary">{{ $p->category }}</td>
                    <td class="text-secondary">{{ $p->unit }}</td>
                    <td>
                        <span style="font-weight:700; color:{{ $p->qty == 0 ? 'var(--red-500)' : ($p->qty <= $p->threshold ? 'var(--amber-500)' : 'var(--text-primary)') }}">
                            {{ $p->qty }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $p->threshold }}</td>
                    @if(auth()->user()->role === 'admin')
                        <td class="text-secondary">Rp {{ number_format($p->buy_price, 0, ',', '.') }}</td>
                    @endif
                    <td style="font-weight:600">Rp {{ number_format($p->sell_price, 0, ',', '.') }}</td>
                    <td>
                        @if($p->qty == 0)
                            <span class="badge badge--red">Out of Stock</span>
                        @elseif($p->qty <= $p->threshold)
                            <span class="badge badge--yellow">Low Stock</span>
                        @else
                            <span class="badge badge--green">Healthy</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:4px">
                            <button type="button" class="btn-icon" title="Detail" style="color:var(--blue-600)" x-data @click="$dispatch('open-detail', { id: {{ $p->id }} })">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            @if(auth()->check() && auth()->user()->role === 'admin')
                            <a href="{{ route('inventory.edit', $p->id) }}" class="btn-icon" title="Edit">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('inventory.destroy', $p->id) }}"
                                  onsubmit="return confirm('Hapus produk {{ addslashes($p->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon" title="Delete" style="color:var(--red-500)">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                        <path d="M9 6V4h6v2"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:32px; color:var(--text-muted)">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links('vendor.pagination.custom') }}
</div>

{{-- ===== SUPPLIER QUICK REFERENCE ===== --}}
@if(auth()->check() && auth()->user()->role === 'admin')
<div class="card">
    <div class="card-header">
        <div class="card-title">Supplier Quick Reference</div>
        <a href="#" class="card-action-link">View All Suppliers →</a>
    </div>
    <div class="card-body">
        <div class="card-grid card-grid--3">
            @php
                $supplierColors = ['var(--blue-600)', 'var(--green-500)', 'var(--amber-500)', 'var(--purple-500)', 'var(--red-500)'];
            @endphp
            @foreach($suppliers as $i => $s)
            <div style="border:1px solid var(--border); border-radius:var(--radius); padding:16px; display:flex; flex-direction:column; gap:10px">
                <div style="display:flex; align-items:center; gap:10px">
                    <div class="avatar" style="background:{{ $supplierColors[$i % count($supplierColors)] }}; width:38px; height:38px; font-size:13px">
                        {{ $s['initials'] }}
                    </div>
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
@endif

{{-- ===== MODAL ===== --}}
<div class="inv-modal-overlay" id="invModalOverlay" onclick="if(event.target === this) closeInvModal()">
    <div class="inv-modal">
        <div class="inv-modal__header">
            <div class="inv-modal__title" id="invModalTitle">Detail</div>
            <button type="button" class="inv-modal__close" onclick="closeInvModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="inv-modal__body" id="invModalBody"></div>
    </div>
</div>

{{-- Alpine Detail Modal --}}
<div x-data="{
        open: false,
        loading: false,
        detailData: null,
        fetchDetail(id) {
            this.open = true;
            this.loading = true;
            this.detailData = null;
            fetch('/inventory/' + id + '/detail')
                .then(res => res.json())
                .then(data => {
                    this.detailData = data;
                    this.loading = false;
                });
        }
    }"
    @open-detail.window="fetchDetail($event.detail.id)"
    x-show="open" 
    class="inv-modal-overlay is-open" 
    style="display: none;" 
    x-transition>
    <div class="inv-modal" @click.outside="open = false">
        <div class="inv-modal__header">
            <div class="inv-modal__title">Product Detail</div>
            <button type="button" class="inv-modal__close" @click="open = false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="inv-modal__body" style="padding: 20px;">
            <div x-show="loading" class="text-center text-muted">Loading...</div>
            <div x-show="!loading && detailData">
                <div style="margin-bottom:16px;">
                    <h3 style="margin:0 0 4px 0" x-text="detailData.name"></h3>
                    <div class="text-sm text-muted" x-text="detailData.sku"></div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:20px">
                <div>
                    <div class="text-sm text-muted">Brand</div>
                    <div style="font-weight:600" x-text="detailData.brand || 'Tanpa Brand'"></div>
                </div>
                <div>
                    <div class="text-sm text-muted">Stock</div>
                    <div style="font-weight:600" x-text="detailData.stock"></div>
                </div>
                <div>
                    <div class="text-sm text-muted">Sell Price</div>
                    <div style="font-weight:600" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(detailData.price)"></div>
                </div>
                <div>
                    <div class="text-sm text-muted">Expired Date</div>
                    <div style="font-weight:600" x-text="detailData.expired_at || '-'"></div>
                </div>
            </div>
                
                <div class="card-title" style="margin-bottom:10px; font-size:14px">Suppliers</div>
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Supplier SKU</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="sup in detailData?.suppliers || []" :key="sup.supplier_sku">
                            <tr>
                                <td x-text="sup.name"></td>
                                <td class="text-secondary" x-text="sup.supplier_sku"></td>
                                <td style="font-weight:600" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(sup.price)"></td>
                            </tr>
                        </template>
                        <tr x-show="!detailData?.suppliers?.length">
                            <td colspan="3" class="text-center text-muted" style="padding: 12px">No suppliers attached.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function openInvModal(sectionId, title) {
    const source = document.getElementById(sectionId);
    if (!source) return;
    const sourceBody = source.querySelector('.card-body, .data-table-wrapper') || source;
    const modalBody  = document.getElementById('invModalBody');
    document.getElementById('invModalTitle').textContent = title;
    modalBody.innerHTML = '';
    modalBody.appendChild(sourceBody.cloneNode(true));
    document.getElementById('invModalOverlay').classList.add('is-open');
    document.body.style.overflow = 'hidden';
}
function closeInvModal() {
    document.getElementById('invModalOverlay').classList.remove('is-open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeInvModal(); });
</script>

@endsection