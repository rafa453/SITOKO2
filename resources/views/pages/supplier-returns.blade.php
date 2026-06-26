@extends('layouts.app')

@section('title', 'Retur Supplier')
@section('page-title', 'Retur Supplier')
@section('page-subtitle', 'Kelola pengembalian barang ke supplier.')

@section('content')

{{-- Stat Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__header"><span class="stat-card__label">Draft</span></div>
        <div class="stat-card__value">{{ $totalDraft }}</div>
        <div class="stat-card__meta text-muted text-sm">Belum dikonfirmasi</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header"><span class="stat-card__label">Dikonfirmasi</span></div>
        <div class="stat-card__value">{{ $totalConfirmed }}</div>
        <div class="stat-card__meta text-muted text-sm">Menunggu supplier terima</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header"><span class="stat-card__label">Selesai Bulan Ini</span></div>
        <div class="stat-card__value">{{ $totalCompleted }}</div>
        <div class="stat-card__meta text-muted text-sm">{{ now()->translatedFormat('F Y') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header"><span class="stat-card__label">Nilai Retur Aktif</span></div>
        <div>
            <div class="stat-card__rp">Rp</div>
            <div class="stat-card__value">{{ number_format($totalValue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card__meta text-muted text-sm">Status dikonfirmasi</div>
    </div>
</div>

{{-- Filter --}}
<div class="card" style="margin-bottom:0">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="{{ route('supplier-returns.index') }}"
              style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nomor retur..."
                   class="form-input" style="width:200px">
            <select name="status" class="form-select" style="width:160px">
                <option value="">Semua Status</option>
                <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('supplier-returns.index') }}" class="btn btn--ghost">Reset</a>
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
                    <th>Nomor Retur</th>
                    <th>Nomor PO</th>
                    <th>Supplier</th>
                    <th>Dibuat Oleh</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $return)
                @php
                    $statusColor = match($return->status) {
                        'draft'     => 'badge--amber',
                        'confirmed' => 'badge--blue',
                        'completed' => 'badge--green',
                        'cancelled' => 'badge--red',
                        default     => '',
                    };
                @endphp
                <tr>
                    <td style="font-weight:600; font-family:monospace">{{ $return->code }}</td>
                    <td style="font-family:monospace">{{ $return->purchaseOrder->code }}</td>
                    <td>{{ $return->supplier->name }}</td>
                    <td class="text-secondary">{{ $return->creator->name }}</td>
                    <td style="font-weight:600">Rp {{ number_format($return->total, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $statusColor }}">{{ strtoupper($return->status) }}</span></td>
                    <td>
                        <a href="{{ route('supplier-returns.show', $return) }}"
                           class="btn btn--secondary" style="padding:5px 10px; font-size:12px">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted)">
                        Belum ada retur supplier.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($returns->hasPages())
    <div style="padding:16px 20px; border-top:1px solid var(--border-light)">
        {{ $returns->links('vendor.pagination.custom') }}
    </div>
    @endif
</div>

@endsection