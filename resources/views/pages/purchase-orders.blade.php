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
    <div class="stat-card" style="cursor:pointer" onclick="openPoPopup('draft')">
        <div class="stat-card__header">
            <span class="stat-card__label">Draft</span>
        </div>
        <div class="stat-card__value">{{ $totalDraft }}</div>
        <div class="stat-card__meta text-muted text-sm">Belum dikonfirmasi</div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="openPoPopup('ordered')">
        <div class="stat-card__header">
            <span class="stat-card__label">Ordered</span>
        </div>
        <div class="stat-card__value">{{ $totalOrdered }}</div>
        <div class="stat-card__meta text-muted text-sm">Menunggu barang tiba</div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="openPoPopup('received')">
        <div class="stat-card__header">
            <span class="stat-card__label">Diterima Bulan Ini</span>
        </div>
        <div class="stat-card__value">{{ $totalReceived }}</div>
        <div class="stat-card__meta text-muted text-sm">{{ now()->translatedFormat('F Y') }}</div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="openPoPopup('value-by-supplier')">
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

{{-- ================= POPUP OVERLAY (BACKDROP) ================= --}}
<div id="po-popup-overlay"
     onclick="closePoPopup()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000">
</div>

{{-- ================= POPUP: DRAFT ================= --}}
<div id="po-popup-draft" class="card po-popup" style="display:none">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Detail Purchase Order — Draft</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closePoPopup()">&times;</button>
        </div>

        <p>Total nilai draft: <strong>Rp {{ number_format($draftValue, 0, ',', '.') }}</strong></p>

        @if($oldestDraft)
            <p style="margin-top:8px">
                Draft tertua: <strong>{{ $oldestDraft->code }}</strong>
                dari supplier <strong>{{ $oldestDraft->supplier->name ?? '-' }}</strong><br>
                <span class="text-muted text-sm">(dibuat {{ $oldestDraft->created_at->diffForHumans() }})</span>
            </p>
        @else
            <p class="text-muted" style="margin-top:8px">Tidak ada draft saat ini.</p>
        @endif
    </div>
</div>

{{-- ================= POPUP: ORDERED ================= --}}
<div id="po-popup-ordered" class="card po-popup" style="display:none">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Detail Purchase Order — Ordered</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closePoPopup()">&times;</button>
        </div>

        <p>Total nilai ordered: <strong>Rp {{ number_format($orderedValue, 0, ',', '.') }}</strong></p>

        <p style="margin-top:8px">
            Jumlah PO overdue (lewat expected date):
            <strong class="{{ $overdueOrdered > 0 ? 'badge--red' : '' }}">{{ $overdueOrdered }}</strong>
        </p>
    </div>
</div>

{{-- ================= POPUP: DITERIMA BULAN INI ================= --}}
<div id="po-popup-received" class="card po-popup" style="display:none">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Detail Purchase Order — Diterima Bulan Ini</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closePoPopup()">&times;</button>
        </div>

        <p>Total nilai diterima bulan ini: <strong>Rp {{ number_format($receivedValueThisMonth, 0, ',', '.') }}</strong></p>

        <p style="margin-top:8px">
            Jumlah PO diterima bulan lalu:
            <strong>{{ $receivedCountLastMonth }}</strong>
            <span class="text-muted text-sm">({{ now()->subMonthNoOverflow()->translatedFormat('F Y') }})</span>
        </p>
    </div>
</div>

{{-- ================= POPUP: NILAI PO AKTIF (TOP 5 SUPPLIER) ================= --}}
<div id="po-popup-value-by-supplier" class="card po-popup" style="display:none; width:480px">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Nilai PO Aktif per Supplier (Top 5)</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closePoPopup()">&times;</button>
        </div>

        @if($valueBySupplier->isEmpty())
            <p class="text-muted">Belum ada PO aktif.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th style="text-align:center">Jumlah PO</th>
                        <th style="text-align:right">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($valueBySupplier as $row)
                        <tr>
                            <td>{{ $row->supplier->name ?? '-' }}</td>
                            <td style="text-align:center">{{ $row->po_count }}</td>
                            <td style="text-align:right">Rp {{ number_format($row->total_value, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<style>
.po-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1001;
    width: 420px;
    max-width: 90vw;
    max-height: 85vh;
    overflow-y: auto;
}
</style>

<script>
function openPoPopup(name) {
    document.getElementById('po-popup-overlay').style.display = 'block';
    document.getElementById('po-popup-' + name).style.display = 'block';
}

function closePoPopup() {
    document.getElementById('po-popup-overlay').style.display = 'none';
    document.querySelectorAll('.po-popup').forEach(function (el) {
        el.style.display = 'none';
    });
}

// Cegah klik di dalam popup ikut menutup popup (event bubbling ke overlay)
document.querySelectorAll('.po-popup').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.stopPropagation();
    });
});

// Tutup popup dengan tombol Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePoPopup();
});
</script>

@endsection