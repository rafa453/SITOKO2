@extends('layouts.app')

@section('title', 'Detail Retur ' . $supplierReturn->code)
@section('page-title', 'Detail Retur Supplier')
@section('page-subtitle', $supplierReturn->code)

@section('header-actions')
    <a href="{{ route('supplier-returns.index') }}" class="btn btn--secondary">← Kembali</a>
@endsection

@section('content')

@php
    $statusColor = match($supplierReturn->status) {
        'draft'     => 'badge--amber',
        'confirmed' => 'badge--blue',
        'completed' => 'badge--green',
        'cancelled' => 'badge--red',
        default     => '',
    };
@endphp

<div class="card-grid card-grid--2" style="align-items:start">

    {{-- Kolom kiri --}}
    <div style="display:flex; flex-direction:column; gap:16px">

        <div class="card">
            <div class="card-header">
                <div class="card-title">Informasi Retur</div>
                <span class="badge {{ $statusColor }}">{{ strtoupper($supplierReturn->status) }}</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:10px">

                @php
                    $rows = [
                        ['label' => 'Nomor Retur', 'value' => $supplierReturn->code],
                        ['label' => 'Nomor PO',    'value' => $supplierReturn->purchaseOrder->code],
                        ['label' => 'Supplier',    'value' => $supplierReturn->supplier->name],
                        ['label' => 'Dibuat Oleh', 'value' => $supplierReturn->creator->name],
                        ['label' => 'Tanggal Buat','value' => $supplierReturn->created_at->format('d M Y H:i')],
                        ['label' => 'Total Nilai', 'value' => 'Rp ' . number_format($supplierReturn->total, 0, ',', '.')],
                    ];
                    if ($supplierReturn->confirmed_at) {
                        $rows[] = ['label' => 'Dikonfirmasi Oleh', 'value' => $supplierReturn->confirmer?->name ?? '—'];
                        $rows[] = ['label' => 'Tanggal Konfirmasi', 'value' => $supplierReturn->confirmed_at->format('d M Y H:i')];
                    }
                    if ($supplierReturn->completed_at) {
                        $rows[] = ['label' => 'Diselesaikan Oleh', 'value' => $supplierReturn->completer?->name ?? '—'];
                        $rows[] = ['label' => 'Tanggal Selesai', 'value' => $supplierReturn->completed_at->format('d M Y H:i')];
                    }
                @endphp

                @foreach($rows as $r)
                <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 0;
                            border-bottom:1px solid var(--border-light)">
                    <span style="color:var(--text-muted)">{{ $r['label'] }}</span>
                    <span style="font-weight:600; text-align:right">{{ $r['value'] }}</span>
                </div>
                @endforeach

                @if($supplierReturn->reason)
                <div style="font-size:12.5px; color:var(--text-muted); margin-top:4px">
                    <span style="font-weight:600; color:var(--text-primary)">Alasan:</span>
                    {{ $supplierReturn->reason }}
                </div>
                @endif

            </div>
        </div>

        {{-- Action buttons --}}
        @if($supplierReturn->canBeConfirmed() || $supplierReturn->canBeCompleted() || $supplierReturn->canBeCancelled())
        <div class="card">
            <div class="card-header">
                <div class="card-title">Update Status</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:8px">

                @if($supplierReturn->canBeConfirmed())
                <form method="POST" action="{{ route('supplier-returns.update-status', $supplierReturn) }}"
                      onsubmit="return confirm('Konfirmasi retur? Stok produk akan otomatis berkurang.')">
                    @csrf
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn--primary w-full" style="justify-content:center">
                        ✓ Konfirmasi Retur — Stok Akan Dikurangi
                    </button>
                </form>
                @endif

                @if($supplierReturn->canBeCompleted())
                <form method="POST" action="{{ route('supplier-returns.update-status', $supplierReturn) }}"
                      onsubmit="return confirm('Tandai retur sebagai selesai? Supplier sudah menerima barang.')">
                    @csrf
                    <input type="hidden" name="action" value="complete">
                    <button type="submit" class="btn btn--primary w-full"
                            style="justify-content:center; background:var(--green-700)">
                        ✓ Tandai Selesai — Supplier Sudah Terima
                    </button>
                </form>
                @endif

                @if($supplierReturn->canBeCancelled())
                <form method="POST" action="{{ route('supplier-returns.update-status', $supplierReturn) }}"
                      onsubmit="return confirm('Batalkan retur ini?')">
                    @csrf
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn--danger w-full" style="justify-content:center">
                        ✗ Batalkan Retur
                    </button>
                </form>
                @endif

            </div>
        </div>
        @endif

    </div>

    {{-- Kolom kanan: items --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Item Diretur</div>
            <span style="font-size:12px; color:var(--text-muted)">{{ $supplierReturn->items->count() }} item</span>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty Retur</th>
                        <th>Harga Beli</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supplierReturn->items as $item)
                    <tr>
                        <td style="font-weight:600">
                            {{ $item->product?->name ?? 'Produk Dihapus' }}
                            @if($item->product?->unit)
                                <span style="font-size:11px; color:var(--text-muted)">({{ $item->product->unit }})</span>
                            @endif
                        </td>
                        <td>{{ number_format($item->qty_returned) }}</td>
                        <td class="text-secondary">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                        <td style="font-weight:600">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right; font-weight:700; padding:12px 16px">Total</td>
                        <td style="font-weight:700; color:var(--red-500); padding:12px 16px">
                            Rp {{ number_format($supplierReturn->total, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection