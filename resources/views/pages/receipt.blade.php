<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            padding: 24px;
        }

        .receipt {
            background: #fff;
            width: 300px;
            padding: 20px 16px;
            border: 1px solid #ddd;
        }

        /* ── Header ── */
        .receipt__store-name {
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .receipt__store-sub {
            font-size: 11px;
            text-align: center;
            color: #555;
            margin-top: 2px;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px dashed #999;
            margin: 10px 0;
        }

        .divider--solid {
            border-top: 1px solid #333;
        }

        /* ── Meta info ── */
        .receipt__meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 11px;
        }

        .receipt__meta span:last-child {
            text-align: right;
            color: #333;
        }

        /* ── Items header ── */
        .items-header {
            display: grid;
            grid-template-columns: 1fr 30px 70px 70px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #555;
            margin-bottom: 4px;
        }

        /* ── Item row ── */
        .item-row {
            display: grid;
            grid-template-columns: 1fr 30px 70px 70px;
            font-size: 11.5px;
            padding: 3px 0;
            border-bottom: 1px dotted #eee;
            align-items: start;
        }

        .item-row__name {
            word-break: break-word;
            padding-right: 6px;
        }

        .item-row__qty,
        .item-row__price,
        .item-row__subtotal {
            text-align: right;
        }

        /* ── Totals ── */
        .totals {
            margin-top: 8px;
        }

        .totals__row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 2px 0;
            color: #333;
        }

        .totals__row--grand {
            font-size: 14px;
            font-weight: 700;
            color: #000;
            padding: 6px 0 4px;
        }

        .totals__row--change {
            color: #16A34A;
            font-weight: 700;
        }

        /* ── Badge status ── */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .status-badge--completed {
            background: #DCFCE7;
            color: #15803D;
        }

        .status-badge--voided {
            background: #FEE2E2;
            color: #B91C1C;
        }

        /* ── Footer ── */
        .receipt__footer {
            text-align: center;
            font-size: 11px;
            color: #777;
            line-height: 1.6;
            margin-top: 4px;
        }

        /* ── Print button (tidak ikut tercetak) ── */
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-print {
            padding: 8px 22px;
            background: #2563EB;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-close {
            padding: 8px 18px;
            background: #fff;
            color: #555;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
        }

        /* ── Print mode ── */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .receipt {
                border: none;
                width: 100%;
                padding: 0;
            }

            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="receipt">

    {{-- Header toko --}}
    @php $store = \App\Models\StoreProfile::get(); @endphp
    <div class="receipt__store-name">{{ $store->store_name }}</div>
    @if($store->store_subtitle)
    <div class="receipt__store-sub">{{ $store->store_subtitle }}</div>
    @endif
    @if($store->address)
    <div class="receipt__store-sub">{{ $store->address }}</div>
    @endif
    @if($store->phone)
    <div class="receipt__store-sub">Telp: {{ $store->phone }}</div>
    @endif

    <hr class="divider">

    {{-- Meta transaksi --}}
    <div class="receipt__meta">
        <span>No. Struk</span>
        <span>{{ $transaction->code }}</span>
    </div>
    <div class="receipt__meta">
        <span>Tanggal</span>
        <span>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
    </div>
    <div class="receipt__meta">
        <span>Kasir</span>
        <span>{{ $transaction->cashier?->name ?? '-' }}</span>
    </div>
    <div class="receipt__meta">
        <span>Metode</span>
        <span>{{ $transaction->payment_method }}</span>
    </div>
    <div class="receipt__meta">
        <span>Status</span>
        <span>
            <span class="status-badge {{ $transaction->status === 'completed' ? 'status-badge--completed' : 'status-badge--voided' }}">
                {{ strtoupper($transaction->status) }}
            </span>
        </span>
    </div>

    <hr class="divider">

    {{-- Items --}}
    <div class="items-header">
    <span>Item</span>
    <span style="text-align:right">Qty</span>
    <span style="text-align:right">Harga</span>
    <span style="text-align:right">Sub</span>
    </div>

    @foreach($transaction->items as $item)
    <div class="item-row">
        <span class="item-row__name">
            {{ $item->product?->name ?? 'Produk Dihapus' }}
            @if($item->unit)
                <span style="font-size:10px; color:#888">({{ $item->unit }})</span>
            @endif
        </span>
        <span class="item-row__qty">{{ $item->qty }}</span>
        <span class="item-row__price">{{ number_format($item->price, 0, ',', '.') }}</span>
        <span class="item-row__subtotal">{{ number_format($item->subtotal, 0, ',', '.') }}</span>
    </div>
    @endforeach

    <hr class="divider">

    {{-- Totals --}}
    <div class="totals">
        <div class="totals__row totals__row--grand">
            <span>TOTAL</span>
            <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
        </div>

        @if($transaction->payment_method === 'Tunai')
        <div class="totals__row">
            <span>Bayar</span>
            <span>Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</span>
        </div>
        @if($transaction->change > 0)
        <div class="totals__row totals__row--change">
            <span>Kembalian</span>
            <span>Rp {{ number_format($transaction->change, 0, ',', '.') }}</span>
        </div>
        @endif
        @endif
    </div>

    <hr class="divider divider--solid">

    {{-- Footer --}}
    <div class="receipt__footer">
        <p>Terima kasih telah berbelanja!</p>
        <p>Barang yang sudah dibeli</p>
        <p>tidak dapat dikembalikan.</p>
    </div>

</div>

{{-- Tombol aksi (tidak ikut cetak) --}}
<div class="print-actions">
    <button class="btn-print" onclick="window.print()">🖨 Cetak Struk</button>
    <button class="btn-close" onclick="window.close()">Tutup</button>
</div>

<script>
    // Auto-trigger print dialog saat halaman selesai load
    // Hapus baris ini kalau tidak mau auto-print
    window.addEventListener('load', () => window.print());
</script>

</body>
</html>