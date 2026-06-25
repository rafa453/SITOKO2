@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Orders')
@section('page-subtitle', 'Kelola pengadaan barang dari supplier.')

@section('header-actions')
    <a href="{{ route('purchase-orders.create') }}" class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Buat PO Baru
    </a>
@endsection

@section('content')

{{-- Stat Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Draft</span>
        </div>
        <div class="stat-card__value">{{ $totalDraft }}</div>
        <div class="stat-card__meta text-muted text-sm">Belum dikonfirmasi</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Ordered</span>
        </div>
        <div class="stat-card__value">{{ $totalOrdered }}</div>
        <div class="stat-card__meta text-muted text-sm">Menunggu barang tiba</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Diterima Bulan Ini</span>
        </div>
        <div class="stat-card__value">{{ $totalReceived }}</div>
        <div class="stat-card__meta text-muted text-sm">{{ now()->translatedFormat('F Y') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Nilai PO Aktif</span>
        </div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">{{ number_format($totalValue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card__meta text-muted text-sm">Status ordered</div>
    </div>
</div>

{{-- Filter --}}
<div class="card" style="margin-bottom:0">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="{{ route('purchase-orders.index') }}"
              style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nomor PO..."
                   class="form-input" style="width:200px">
            <select name="supplier_id" class="form-select" style="width:180px">
                <option value="">Semua Supplier</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="form-select" style="width:140px">
                <option value="">Semua Status</option>
                <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                <option value="ordered"   {{ request('status') === 'ordered'   ? 'selected' : '' }}>Ordered</option>
                <option value="received"  {{ request('status') === 'received'  ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            @if(request('search') || request('status') || request('supplier_id'))
                <a href="{{ route('purchase-orders.index') }}" class="btn btn--ghost">Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nomor PO</th>
                    <th>Supplier</th>
                    <th>Dibuat Oleh</th>
                    <th>Expected</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $po)
                @php
                    $statusColor = match($po->status) {
                        'draft'     => 'badge--amber',
                        'ordered'   => 'badge--blue',
                        'received'  => 'badge--green',
                        'cancelled' => 'badge--red',
                        default     => '',
                    };
                @endphp
                <tr>
                    <td style="font-weight:600; font-family:monospace">{{ $po->code }}</td>
                    <td>{{ $po->supplier->name }}</td>
                    <td class="text-secondary">{{ $po->creator->name }}</td>
                    <td class="text-secondary">
                        {{ $po->expected_at ? $po->expected_at->format('d M Y') : '—' }}
                    </td>
                    <td style="font-weight:600">Rp {{ number_format($po->total, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $statusColor }}">{{ strtoupper($po->status) }}</span></td>
                    <td>
                        <a href="{{ route('purchase-orders.show', $po) }}"
                           class="btn btn--secondary" style="padding:5px 10px; font-size:12px">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted)">
                        Belum ada Purchase Order.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($purchaseOrders->hasPages())
    <div style="padding:16px 20px; border-top:1px solid var(--border-light)">
        {{ $purchaseOrders->links('vendor.pagination.custom') }}
    </div>
    @endif
</div>

@endsection