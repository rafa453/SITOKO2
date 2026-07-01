@extends('layouts.app')

@section('title', 'Retur Supplier')
@section('page-title', 'Retur Supplier')
@section('page-subtitle', 'Kelola pengembalian barang ke supplier.')

@section('content')

{{-- Stat Cards --}}
<div class="stats-grid">
    <div class="stat-card" style="cursor:pointer" onclick="openReturnPopup('draft')">
        <div class="stat-card__header"><span class="stat-card__label">Draft</span></div>
        <div class="stat-card__value">{{ $totalDraft }}</div>
        <div class="stat-card__meta text-muted text-sm">Belum dikonfirmasi</div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="openReturnPopup('confirmed')">
        <div class="stat-card__header"><span class="stat-card__label">Dikonfirmasi</span></div>
        <div class="stat-card__value">{{ $totalConfirmed }}</div>
        <div class="stat-card__meta text-muted text-sm">Menunggu supplier terima</div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="openReturnPopup('completed')">
        <div class="stat-card__header"><span class="stat-card__label">Selesai Bulan Ini</span></div>
        <div class="stat-card__value">{{ $totalCompleted }}</div>
        <div class="stat-card__meta text-muted text-sm">{{ now()->translatedFormat('F Y') }}</div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="openReturnPopup('value-by-supplier')">
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

{{-- ================= POPUP OVERLAY (BACKDROP) ================= --}}
<div id="return-popup-overlay"
     onclick="closeReturnPopup()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000">
</div>

{{-- ================= POPUP: DRAFT ================= --}}
<div id="return-popup-draft" class="card return-popup" style="display:none">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Detail Retur — Draft</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closeReturnPopup()">&times;</button>
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

{{-- ================= POPUP: DIKONFIRMASI ================= --}}
<div id="return-popup-confirmed" class="card return-popup" style="display:none">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Detail Retur — Dikonfirmasi</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closeReturnPopup()">&times;</button>
        </div>

        <p>Total nilai dikonfirmasi: <strong>Rp {{ number_format($confirmedValue, 0, ',', '.') }}</strong></p>

        @if($oldestConfirmed)
            <p style="margin-top:8px">
                Menunggu paling lama: <strong>{{ $oldestConfirmed->code }}</strong>
                dari supplier <strong>{{ $oldestConfirmed->supplier->name ?? '-' }}</strong><br>
                <span class="text-muted text-sm">
                    (dikonfirmasi {{ $oldestConfirmed->confirmed_at ? $oldestConfirmed->confirmed_at->diffForHumans() : '-' }})
                </span>
            </p>
        @else
            <p class="text-muted" style="margin-top:8px">Tidak ada retur yang dikonfirmasi saat ini.</p>
        @endif
    </div>
</div>

{{-- ================= POPUP: SELESAI BULAN INI ================= --}}
<div id="return-popup-completed" class="card return-popup" style="display:none">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Detail Retur — Selesai Bulan Ini</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closeReturnPopup()">&times;</button>
        </div>

        <p>Total nilai selesai bulan ini: <strong>Rp {{ number_format($completedValueThisMonth, 0, ',', '.') }}</strong></p>

        <p style="margin-top:8px">
            Jumlah selesai bulan lalu:
            <strong>{{ $completedCountLastMonth }}</strong>
            <span class="text-muted text-sm">({{ now()->subMonthNoOverflow()->translatedFormat('F Y') }})</span>
        </p>
    </div>
</div>

{{-- ================= POPUP: NILAI RETUR AKTIF (TOP 5 SUPPLIER) ================= --}}
<div id="return-popup-value-by-supplier" class="card return-popup" style="display:none; width:480px">
    <div class="card-body" style="padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
            <h3 style="margin:0">Nilai Retur Aktif per Supplier (Top 5)</h3>
            <button type="button" class="btn btn--ghost" style="padding:2px 10px" onclick="closeReturnPopup()">&times;</button>
        </div>

        @if($valueBySupplier->isEmpty())
            <p class="text-muted">Belum ada retur aktif.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th style="text-align:center">Jumlah Retur</th>
                        <th style="text-align:right">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($valueBySupplier as $row)
                        <tr>
                            <td>{{ $row->supplier->name ?? '-' }}</td>
                            <td style="text-align:center">{{ $row->return_count }}</td>
                            <td style="text-align:right">Rp {{ number_format($row->total_value, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<style>
.return-popup {
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
function openReturnPopup(name) {
    document.getElementById('return-popup-overlay').style.display = 'block';
    document.getElementById('return-popup-' + name).style.display = 'block';
}

function closeReturnPopup() {
    document.getElementById('return-popup-overlay').style.display = 'none';
    document.querySelectorAll('.return-popup').forEach(function (el) {
        el.style.display = 'none';
    });
}

// Cegah klik di dalam popup ikut menutup popup (event bubbling ke overlay)
document.querySelectorAll('.return-popup').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.stopPropagation();
    });
});

// Tutup popup dengan tombol Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeReturnPopup();
});
</script>

@endsection