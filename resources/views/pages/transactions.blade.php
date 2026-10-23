@extends('layouts.app')

@section('title', 'Transaksi')
@section('page-title', 'Transaksi')
@section('page-subtitle', 'Pantau, cari, dan tinjau semua transaksi penjualan.')

@section('header-actions')
    <form method="GET" action="{{ route('transactions.index') }}" class="filter-bar" id="filterForm" style="display:flex; align-items:center; gap:8px">
        <div class="search-input-wrapper" style="width:200px">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="search" class="form-input" placeholder="Cari ID..."
                value="{{ request('search') }}">
        </div>

        <x-filter-popover title="Filter Transaksi" form-id="filterForm" :is-active="request()->filled('search') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('method') || request()->filled('status')">
            <div style="display:flex; flex-direction:column; gap:6px">
                <input type="date" name="date_from" class="form-input" value="{{ $dateFrom->format('Y-m-d') }}">
                <input type="date" name="date_to" class="form-input" value="{{ $dateTo->format('Y-m-d') }}">
            </div>
            <select name="method" class="form-select">
                <option value="">Semua Metode</option>
                @foreach($paymentBreakdown as $pm)
                    <option value="{{ $pm->payment_method }}" {{ request('method')===$pm->payment_method ? 'selected' : '' }}>
                        {{ $pm->payment_method }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Selesai</option>
                <option value="voided" {{ request('status')==='voided' ? 'selected' : '' }}>Dibatal</option>
            </select>
        </x-filter-popover>

        @if(auth()->user()->role === 'admin')
        <a href="{{ route('transactions.export', request()->only(['date_from','date_to','search','method','status'])) }}"
           class="btn btn--secondary btn--sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Export
        </a>
        @endif
    </form>
@endsection

@section('content')

@php
    // Bikin label tanggal dinamis
    $dateLabel = \Carbon\Carbon::parse($dateFrom)->format('d M') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
    if ($dateFrom === $dateTo) {
        $dateLabel = \Carbon\Carbon::parse($dateFrom)->format('d M Y');
    }
@endphp

@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

<div x-data="{ openModal: null }">
    @if(auth()->user()->role === 'admin')
    {{-- ===== STAT CARDS ===== --}}
    <div class="stats-grid">

        <div class="stat-card" style="cursor:pointer" @click="openModal = 'all'">
            <div class="stat-card__header">
                <span class="stat-card__label">Total Transaksi</span>
            </div>
            <div class="stat-card__value">{{ number_format($totalTransactions) }}</div>
            <div class="stat-card__meta text-muted text-sm">{{ $dateLabel }}</div>
        </div>

        <div class="stat-card" style="cursor:pointer" @click="openModal = 'revenue'">
            <div class="stat-card__header">
                <span class="stat-card__label">Total Pendapatan</span>
            </div>
            <div>
                <div class="stat-card__rp">Rp</div>
                <div class="stat-card__value">{{ number_format($totalRevenue, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card__meta text-muted text-sm">Completed ({{ $dateLabel }})</div>
        </div>

        <div class="stat-card" style="cursor:pointer; border-color:#FEE2E2; background:#FEF2F2" @click="openModal = 'voided'">
            <div class="stat-card__header">
                <span class="stat-card__label" style="color:#991B1B">Dibatal</span>
                @if($voidedCount > 0)
                    <span class="badge badge--red">Perlu Tindakan</span>
                @endif
            </div>
            <div class="stat-card__value" style="color:#EF4444">{{ $voidedCount }}</div>
            <div class="stat-card__meta text-sm" style="color:#991B1B">
                Value: Rp {{ number_format($voidedValue, 0, ',', '.') }}
            </div>
        </div>

        <div class="stat-card" style="cursor:pointer" @click="openModal = 'basket'">
            <div class="stat-card__header">
                <span class="stat-card__label">Rata-rata Nilai Belanja</span>
            </div>
            <div>
                <div class="stat-card__rp">Rp</div>
                <div class="stat-card__value">{{ number_format($avgBasket, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card__meta text-muted text-sm">Rata-rata ({{ $dateLabel }})</div>
        </div>

    </div>

    {{-- ===== POPUP MODALS ===== --}}
    <div x-show="openModal" 
         x-transition.opacity
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;"
         @click.self="openModal = null"
         x-cloak>

        <div class="card" 
             x-show="openModal"
             x-transition.scale.85
             style="width: 520px; max-width: 100%; max-height: 85vh; display: flex; flex-direction: column; background: var(--surface); border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); margin: auto;">
            
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-light)">
                <div class="card-title" style="font-weight: 700; font-size: 16px;" x-text="
                    openModal === 'all' ? 'All Transactions Today' :
                    openModal === 'revenue' ? 'Transaksi Selesai' :
                    openModal === 'voided' ? 'Transaksi Dibatalkan' :
                    'Analisis Rata-rata Nilai Belanja'
                "></div>
                <button class="btn-icon" @click="openModal = null" style="background: none; border: none; cursor: pointer; color: var(--text-muted)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div style="overflow-y: auto; flex: 1; padding: 10px 0;">

                <template x-if="openModal === 'all'">
                    <div>
                        @forelse($popupAllTx as $trx)
                        <div style="display:flex; justify-content:space-between; padding:12px 20px; border-bottom:1px solid var(--border-light); font-size:13px">
                            <div>
                                <div style="font-weight:600; color:var(--blue-600)">{{ $trx->code }}</div>
                                <div class="text-muted text-sm">{{ $trx->created_at->format('H:i') }} • {{ $trx->cashier?->name ?? '-' }}</div>
                            </div>
                            <div style="text-align:right">
                                <div style="font-weight:600; margin-bottom: 4px;">Rp {{ number_format($trx->total, 0, ',', '.') }}</div>
                                @if($trx->status === 'completed')
                                    <span class="badge badge--green">COMPLETED</span>
                                @else
                                    <span class="badge badge--red">VOIDED</span>
                                @endif
                            </div>
                        </div>
                        @empty
                            <p class="text-muted text-sm" style="padding:20px; text-align: center;">Tidak ada transaksi hari ini.</p>
                        @endforelse
                    </div>
                </template>

                <template x-if="openModal === 'revenue'">
                    <div>
                        @forelse($popupRevenueTx as $trx)
                        <div style="display:flex; justify-content:space-between; padding:12px 20px; border-bottom:1px solid var(--border-light); font-size:13px">
                            <div>
                                <div style="font-weight:600; color:var(--blue-600)">{{ $trx->code }}</div>
                                <div class="text-muted text-sm">{{ $trx->created_at->format('H:i') }} • {{ $trx->cashier?->name ?? '-' }}</div>
                            </div>
                            <div style="font-weight:600; align-self: center;">Rp {{ number_format($trx->total, 0, ',', '.') }}</div>
                        </div>
                        @empty
                            <p class="text-muted text-sm" style="padding:20px; text-align: center;">Tidak ada transaksi selesai hari ini.</p>
                        @endforelse
                    </div>
                </template>

                <template x-if="openModal === 'voided'">
                    <div>
                        @forelse($popupVoidedTx as $trx)
                        <div style="display:flex; justify-content:space-between; padding:12px 20px; border-bottom:1px solid var(--border-light); font-size:13px">
                            <div>
                                <div style="font-weight:600; color:var(--blue-600)">{{ $trx->code }}</div>
                                <div class="text-muted text-sm">{{ $trx->created_at->format('H:i') }} • {{ $trx->cashier?->name ?? '-' }}</div>
                            </div>
                            <div style="font-weight:600; color:#EF4444; align-self: center;">Rp {{ number_format($trx->total, 0, ',', '.') }}</div>
                        </div>
                        @empty
                            <p class="text-muted text-sm" style="padding:20px; text-align: center;">Tidak ada transaksi dibatalkan hari ini.</p>
                        @endforelse
                    </div>
                </template>

                <template x-if="openModal === 'basket'">
                    <div style="padding: 16px 20px;">
                        <div style="text-align: center; padding: 20px 0; background: var(--border-light); border-radius: 6px; margin-bottom: 20px;">
                            <div style="font-size: 13px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Rata-rata Nilai per Transaksi</div>
                            <div style="font-size: 28px; font-weight: 800; color: var(--blue-600); margin-top: 4px;">
                                Rp {{ number_format($avgBasket, 0, ',', '.') }}
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px;">
                            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border-light)">
                                <span style="color: var(--text-secondary)">Total Omset ({{ $dateLabel }})</span>
                                <span style="font-weight: 600;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border-light)">
                                <span style="color: var(--text-secondary)">Volume Transaksi Berhasil</span>
                                <span style="font-weight: 600;">{{ $totalTransactions - $voidedCount }} Transaksi</span>
                            </div>
                        </div>

                        <div style="margin-top: 24px; padding: 12px; background: #EFF6FF; border-left: 4px solid #2563EB; border-radius: 4px; font-size: 12.5px; color: #1E40AF; line-height: 1.5;">
                            <strong>💡 Metrik Basket Size:</strong> Nilai ini menunjukkan rata-rata jumlah uang yang dibelanjakan pelanggan dalam rentang waktu yang dipilih.
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>
    @endif
</div>

@if(auth()->user()->role === 'admin')
{{-- ===== CHART + PAYMENT BREAKDOWN ===== --}}
<div class="card-grid card-grid--60-40" style="margin-top: 24px;">

    {{-- Daily Volume Chart --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Volume Transaksi Harian</div>
                <div class="card-subtitle">Activity patterns ({{ $dateLabel }})</div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-wrapper" style="height:180px">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Payment Breakdown --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Rincian Pembayaran</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
            @php
                $totalTrx = $paymentBreakdown->sum('trx_count') ?: 1;
                $colors   = ['#2563EB','#6366F1','#22C55E','#F59E0B','#EF4444'];
            @endphp

            @forelse($paymentBreakdown as $i => $pm)
            @php $pct = round($pm->trx_count / $totalTrx * 100); @endphp
            <div>
                <div style="display:flex; justify-content:space-between; margin-bottom:5px; align-items:center">
                    <div style="display:flex; align-items:center; gap:8px">
                        <span class="status-dot" style="background:{{ $colors[$i % count($colors)] }}"></span>
                        <span style="font-weight:600; font-size:13px">{{ $pm->payment_method }}</span>
                        <span class="text-muted text-sm">{{ $pm->trx_count }} trx</span>
                    </div>
                    <span style="font-weight:700; font-size:13px">Rp {{ number_format($pm->revenue, 0, ',', '.') }}</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar__fill" style="width:{{ $pct }}%; background:{{ $colors[$i % count($colors)] }}"></div>
                </div>
            </div>
            @empty
                <p class="text-muted text-sm">Tidak ada transaksi hari ini.</p>
            @endforelse
        </div>
    </div>

</div>
@endif

{{-- ===== TRANSACTION LOG + DETAIL MODAL ===== --}}
<div x-data="{
    selectedId: null,
    selected: null,
    closeModal() {
        this.selected = null;
        this.selectedId = null;
    },
    loadDetail(trx) {
        this.selectedId = trx.id;
        this.selected = JSON.parse(JSON.stringify(trx));
    }
}" @keyup.escape.window="closeModal">

    {{-- Detailed Log --}}
    <div class="card card--overflow-x" style="margin-top:24px">
        <div class="card-header">
            <div class="card-title">Log Detail</div>
        </div>

        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Timestamp</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Method</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    @php
                        $trxData = json_encode([
                            'id'             => $trx->id,
                            'code'           => $trx->code,
                            'status'         => $trx->status,
                            'created_at'     => $trx->created_at->format('d M Y • H:i'),
                            'cashier'        => $trx->cashier?->name ?? '-',
                            'payment_method' => $trx->payment_method,
                            'total'          => $trx->total,
                            'amount_paid'    => $trx->amount_paid,
                            'change'         => $trx->change,
                            'items' => $trx->items->map(fn($i) => [
                                'name'     => $i->product?->name ?? 'Produk Dihapus',
                                'unit'     => $i->unit ?? '-',
                                'qty'      => $i->qty,
                                'price'    => $i->price,
                                'subtotal' => $i->subtotal,
                            ]),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                    @endphp
                    <tr style="cursor:pointer"
                        data-trx="{{ $trxData }}"
                        @click="loadDetail(JSON.parse($el.dataset.trx))">
                        <td>
                            <span style="font-weight:600; font-size:12.5px; color:var(--blue-600)">
                                {{ $trx->code }}
                            </span>
                        </td>
                        <td class="table-id">{{ $trx->created_at->format('d/m H:i:s') }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:7px">
                                <div class="avatar avatar--blue" style="width:26px; height:26px; font-size:10px">
                                    {{ strtoupper(substr($trx->cashier?->name ?? '?', 0, 2)) }}
                                </div>
                                <span style="font-size:12.5px">{{ $trx->cashier?->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $trx->items->count() }} items</td>
                        <td>
                            <span class="badge badge--blue">{{ $trx->payment_method }}</span>
                        </td>
                        <td style="font-weight:600">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                        <td>
                            @if($trx->status === 'completed')
                                <span class="badge badge--green">COMPLETED</span>
                            @else
                                <span class="badge badge--red">VOIDED</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn-icon" style="font-size:11px; width:24px; height:24px">›</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted)">
                            Transaksi tidak ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:12px 16px">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>

    {{-- Detail Modal --}}
    <div x-show="selected" x-cloak
         role="dialog" aria-modal="true" aria-label="Detail transaksi"
         @click.self="closeModal"
         style="position:fixed; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.5)">
        <div x-show="selected" x-transition.scale.85
             style="width:100%; max-width:440px; max-height:90vh; display:flex; flex-direction:column; background:var(--surface); border-radius:var(--radius); box-shadow:0 20px 50px rgba(15,23,42,.25); overflow:hidden">
            <template x-if="selected">
                <div style="display:flex; flex-direction:column; max-height:90vh">
                    <div class="card-header" style="padding:14px 18px; flex-shrink:0">
                        <div>
                            <span class="badge" :class="selected.status === 'completed' ? 'badge--green' : 'badge--red'"
                                  style="margin-bottom:6px; display:inline-flex"
                                  x-text="selected.status.toUpperCase()"></span>
                            <div style="font-size:14px; font-weight:700; font-family:var(--font-mono); letter-spacing:-.5px"
                                 x-text="selected.code"></div>
                        </div>
                        <button class="btn-icon" @click="closeModal" aria-label="Tutup detail transaksi">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <div style="overflow-y:auto; flex:1">
                        <div style="padding:12px 18px; border-bottom:1px solid var(--border-light); display:grid; grid-template-columns:1fr 1fr; gap:12px">
                            <div>
                                <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:3px">Date & Time</div>
                                <div style="font-size:12.5px; font-weight:600" x-text="selected.created_at"></div>
                            </div>
                            <div>
                                <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:3px">Cashier</div>
                                <div style="font-size:12.5px; font-weight:600" x-text="selected.cashier"></div>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div style="padding:14px 18px">
                            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:10px">Itemized Receipt</div>

                            <div style="display:grid; grid-template-columns:1fr auto auto auto; gap:4px 12px; font-size:12px; margin-bottom:4px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.4px">
                                <span>Item</span><span>Qty</span><span>Price</span><span>Sub</span>
                            </div>

                            <template x-for="item in selected.items" :key="item.name">
                                <div style="display:flex; justify-content:space-between; align-items:start;
                                            padding:8px 0; border-bottom:1px solid var(--border-light); font-size:13px">
                                    <div>
                                        <div style="font-weight:500" x-text="item.name"></div>
                                        <div style="font-size:11.5px; color:var(--text-muted)"
                                            x-text="`${item.qty} ${item.unit} × Rp ${Number(item.price).toLocaleString('id-ID')}`"></div>
                                    </div>
                                    <div style="font-weight:600; flex-shrink:0; margin-left:12px"
                                        x-text="`Rp ${Number(item.subtotal).toLocaleString('id-ID')}`"></div>
                                </div>
                            </template>

                            {{-- Totals --}}
                            <div style="margin-top:12px; display:flex; flex-direction:column; gap:6px; font-size:12.5px">
                                <div style="display:flex; justify-content:space-between; color:var(--text-secondary)">
                                    <span>Subtotal</span>
                                    <span x-text="Number(selected.total).toLocaleString('id-ID')"></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; font-weight:800; font-size:14px">
                                    <span>Total</span>
                                    <span style="color:var(--blue-600)" x-text="'Rp ' + Number(selected.total).toLocaleString('id-ID')"></span>
                                </div>
                                <template x-if="selected.payment_method === 'Tunai'">
                                    <div style="display:flex; justify-content:space-between; color:var(--text-secondary)">
                                        <span>Bayar</span>
                                        <span x-text="'Rp ' + Number(selected.amount_paid).toLocaleString('id-ID')"></span>
                                    </div>
                                </template>
                                <template x-if="selected.payment_method === 'Tunai' && selected.change > 0">
                                    <div style="display:flex; justify-content:space-between; color:#16A34A; font-weight:700">
                                        <span>Kembalian</span>
                                        <span x-text="'Rp ' + Number(selected.change).toLocaleString('id-ID')"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Payment method --}}
                            <div style="margin-top:12px; padding:10px 12px; background:var(--border-light); border-radius:var(--radius-sm); display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:600">
                                <span class="badge badge--blue" x-text="selected.payment_method"></span>
                                <span x-text="'Paid via ' + selected.payment_method"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="padding:12px 18px 16px; display:flex; gap:8px; border-top:1px solid var(--border-light); flex-shrink:0">
                        <button class="btn btn--secondary"
                                style="flex:1; justify-content:center; font-size:12.5px"
                                @click="window.open(`{{ url('transactions') }}/${selected.id}/receipt`, '_blank', 'width=420,height=700,scrollbars=yes')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"/>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <rect x="6" y="14" width="12" height="8"/>
                            </svg>
                            Print Receipt
                        </button>
                        @if(auth()->user()->role === 'admin')
                        <form method="POST" :action="`{{ url('transactions') }}/${selected.id}/void`">
                            @csrf
                            <button type="submit" class="btn btn--danger"
                                    style="font-size:12.5px"
                                    x-show="selected.status === 'completed'"
                                    onclick="return confirm('Void transaksi ini?')">
                                Void
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const ctx = document.getElementById('dailyChart');
    if (!ctx) return;

    const chartData  = @json($chartData);
    const groupByHour = @json($groupByHour);

    const labels = chartData.map(d =>
        groupByHour
            ? String(d.label).padStart(2,'0') + ':00'
            : d.label
    );
    const counts = chartData.map(d => d.count);
    const Chart = await window.loadChart();

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
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
                y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 }, color: '#94A3B8', stepSize: 1 } }
            }
        }
    });
});
</script>
@endpush