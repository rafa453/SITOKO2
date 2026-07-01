@extends('layouts.app')

@section('title', 'Detail PO ' . $purchaseOrder->code)
@section('page-title', 'Detail Purchase Order')
@section('page-subtitle', $purchaseOrder->code)

@section('header-actions')
    <a href="{{ route('purchase-orders.index') }}" class="btn btn--secondary">← Kembali</a>
@endsection

@section('content')

@php
    $statusColor = match($purchaseOrder->status) {
        'draft'     => 'badge--amber',
        'ordered'   => 'badge--blue',
        'received'  => 'badge--green',
        'cancelled' => 'badge--red',
        default     => '',
    };
@endphp

<div class="card-grid card-grid--2" style="align-items:start">

    {{-- Kolom kiri: info PO --}}
    <div style="display:flex; flex-direction:column; gap:16px">

        <div class="card">
            <div class="card-header">
                <div class="card-title">Informasi PO</div>
                <span class="badge {{ $statusColor }}">{{ strtoupper($purchaseOrder->status) }}</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:10px">

                @php
                    $rows = [
                        ['label' => 'Nomor PO',    'value' => $purchaseOrder->code],
                        ['label' => 'Supplier',    'value' => $purchaseOrder->supplier->name],
                        ['label' => 'Dibuat Oleh', 'value' => $purchaseOrder->creator->name],
                        ['label' => 'Tanggal Buat','value' => $purchaseOrder->created_at->format('d M Y H:i')],
                        ['label' => 'Expected',    'value' => $purchaseOrder->expected_at?->format('d M Y') ?? '—'],
                        ['label' => 'Total Nilai', 'value' => 'Rp ' . number_format($purchaseOrder->total, 0, ',', '.')],
                    ];
                    if ($purchaseOrder->status === 'received') {
                        $rows[] = ['label' => 'Diterima Oleh', 'value' => $purchaseOrder->receiver?->name ?? '—'];
                        $rows[] = ['label' => 'Tanggal Terima', 'value' => $purchaseOrder->received_at?->format('d M Y H:i') ?? '—'];
                    }
                @endphp

                @foreach($rows as $r)
                <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 0;
                            border-bottom:1px solid var(--border-light)">
                    <span style="color:var(--text-muted)">{{ $r['label'] }}</span>
                    <span style="font-weight:600; text-align:right">{{ $r['value'] }}</span>
                </div>
                @endforeach

                @if($purchaseOrder->notes)
                <div style="font-size:12.5px; color:var(--text-muted); margin-top:4px">
                    <span style="font-weight:600; color:var(--text-primary)">Catatan:</span>
                    {{ $purchaseOrder->notes }}
                </div>
                @endif

            </div>
        </div>

        {{-- Action buttons --}}
        @if($purchaseOrder->status !== 'draft')
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <div class="card-title">Dokumen & Komunikasi</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:8px">
                <a href="{{ route('purchase-orders.pdf', $purchaseOrder) }}" 
                   target="_blank"
                   class="btn btn--secondary w-full" style="justify-content:center">
                   Download Faktur PDF
                </a>
                
                @php
                    $phone = ltrim($purchaseOrder->supplier->phone, '0+');
                    $message = "Kepada Yth. {$purchaseOrder->supplier->name},\n" .
                               "Bersama ini kami mengirimkan Purchase Order #{$purchaseOrder->code}.\n" .
                               "Total: Rp " . number_format($purchaseOrder->total, 0, ',', '.') . "\n" .
                               "Mohon konfirmasi ketersediaan barang.\n" .
                               "Terima kasih.";
                    $waUrl = "https://wa.me/62" . $phone . "?text=" . urlencode($message);
                @endphp
                <a href="{{ $waUrl }}" 
                   target="_blank"
                   class="btn btn--primary w-full" style="justify-content:center">
                   Kirim ke Supplier (WA)
                </a>
            </div>
        </div>
        @endif

        @if($purchaseOrder->canBeOrdered() || $purchaseOrder->canBeReceived() || $purchaseOrder->canBeCancelled() || $purchaseOrder->status === 'received')
        <div class="card">
            <div class="card-header">
                <div class="card-title">Update Status</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:8px">

                @if($purchaseOrder->canBeOrdered())
                <form method="POST" action="{{ route('purchase-orders.update-status', $purchaseOrder) }}">
                    @csrf
                    <input type="hidden" name="action" value="order">
                    <button type="submit" class="btn btn--primary w-full" style="justify-content:center">
                        ✓ Konfirmasi — Ubah ke Ordered
                    </button>
                </form>
                @endif

                @if($purchaseOrder->canBeEdited())
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn--secondary">
                    Edit PO
                </a>
                @endif

                @if($purchaseOrder->canBeReceived())
                <form method="POST" action="{{ route('purchase-orders.update-status', $purchaseOrder) }}">
                    @csrf
                    <input type="hidden" name="action" value="receive">
                    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:10px">
                        <div style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em">
                            Qty Diterima per Item
                        </div>
                        @foreach($purchaseOrder->items as $item)
                        @php $sisa = $item->qty_ordered - $item->qty_received; @endphp
                        @if($sisa > 0)
                        <div style="display:flex; align-items:center; gap:10px; font-size:13px">
                            <span style="flex:1; font-weight:500">
                                {{ $item->product?->name ?? 'Produk Dihapus' }}
                                <span style="color:var(--text-muted); font-size:11px">
                                    (sudah: {{ $item->qty_received }}/{{ $item->qty_ordered }})
                                </span>
                            </span>
                            <input type="number"
                                   name="received_qtys[{{ $item->id }}]"
                                   class="form-input"
                                   style="width:90px; padding:5px 8px; font-size:13px"
                                   min="0" max="{{ $sisa }}" value="{{ $sisa }}" placeholder="0">
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn--primary w-full"
                            style="justify-content:center; background:var(--green-700)"
                            onclick="return confirm('Konfirmasi penerimaan barang? Stok akan diupdate sesuai qty yang diisi.')">
                        ✓ Terima Barang — Update Stok
                    </button>
                </form>
                @endif

                @if($purchaseOrder->canBeCancelled())
                <form method="POST" action="{{ route('purchase-orders.update-status', $purchaseOrder) }}"
                      onsubmit="return confirm('Batalkan PO ini?')">
                    @csrf
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn--danger w-full" style="justify-content:center">
                        ✗ Batalkan PO
                    </button>
                </form>
                @endif

                @if($purchaseOrder->status === 'received')
                <a href="{{ route('supplier-returns.create', ['purchase_order_id' => $purchaseOrder->id]) }}"
                   class="btn btn--secondary w-full" style="justify-content:center">
                    ↩ Buat Retur ke Supplier
                </a>
                @endif

            </div>
        </div>
        @endif

    </div>

    {{-- Kolom kanan: items --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Item Pesanan</div>
            <span style="font-size:12px; color:var(--text-muted)">{{ $purchaseOrder->items->count() }} item</span>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty Pesan</th>
                        <th>Qty Terima</th>
                        <th>Harga Beli</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                    <tr>
                        <td style="font-weight:600">
                            {{ $item->product?->name ?? 'Produk Dihapus' }}
                            @if($item->product?->unit)
                                <span style="font-size:11px; color:var(--text-muted)">({{ $item->product->unit }})</span>
                            @endif
                        </td>
                        <td>{{ number_format($item->qty_ordered) }}</td>
                        <td>
                            @if($purchaseOrder->status === 'received')
                                <span style="color:var(--green-700); font-weight:600">
                                    {{ number_format($item->qty_received) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-secondary">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                        <td style="font-weight:600">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:right; font-weight:700; padding:12px 16px">Total</td>
                        <td style="font-weight:700; color:var(--blue-600); padding:12px 16px">
                            Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection