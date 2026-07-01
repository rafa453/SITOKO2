<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchaseOrder->code }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2563EB; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1E3A8A; font-size: 24px; }
        .header h2 { margin: 5px 0 0 0; color: #2563EB; font-size: 18px; font-weight: normal; }
        .info-table { width: 100%; margin-bottom: 30px; }
        .info-table td { vertical-align: top; width: 50%; }
        .info-box { padding: 15px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 4px; }
        .info-title { font-weight: bold; margin-bottom: 8px; color: #475569; font-size: 12px; text-transform: uppercase; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #2563EB; color: #ffffff; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; }
        .items-table td { padding: 10px; border-bottom: 1px solid #E2E8F0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; background: #F8FAFC; border-top: 2px solid #2563EB; }
        .footer { margin-top: 40px; font-size: 12px; color: #64748B; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <h1>SITOKO2</h1>
        <h2>PURCHASE ORDER</h2>
        <div style="margin-top:5px; font-size:16px; font-weight:bold;"># {{ $purchaseOrder->code }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="padding-right: 15px;">
                <div class="info-box">
                    <div class="info-title">Informasi Pemesan</div>
                    <strong>SITOKO2</strong><br>
                    Admin: {{ $purchaseOrder->creator->name }}<br>
                    Tanggal: {{ $purchaseOrder->created_at->format('d F Y') }}<br>
                    Status: {{ strtoupper($purchaseOrder->status) }}
                </div>
            </td>
            <td style="padding-left: 15px;">
                <div class="info-box">
                    <div class="info-title">Kepada (Supplier)</div>
                    <strong>{{ $purchaseOrder->supplier->name }}</strong><br>
                    Kategori: {{ $purchaseOrder->supplier->category ?? '-' }}<br>
                    Telepon: +62 {{ $supplierPhone }}<br>
                    Alamat: {{ $purchaseOrder->supplier->address ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="45%">Nama Produk</th>
                <th width="15%" class="text-center">Qty</th>
                <th width="15%" class="text-right">Harga Satuan</th>
                <th width="20%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product->name }}</strong><br>
                    <span style="font-size:11px; color:#64748B;">SKU: {{ $item->product->sku }}</span>
                </td>
                <td class="text-center">{{ $item->qty_ordered }} {{ $item->product->unit }}</td>
                <td class="text-right">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right" style="padding:12px 10px;">TOTAL KESELURUHAN</td>
                <td class="text-right" style="padding:12px 10px;">Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($purchaseOrder->notes)
    <div class="info-box" style="margin-bottom:20px;">
        <div class="info-title">Catatan</div>
        {{ $purchaseOrder->notes }}
    </div>
    @endif

    <div class="footer">
        Dokumen ini dibuat secara otomatis oleh sistem SITOKO2 pada {{ now()->format('d F Y H:i') }}.<br>
        Status Dokumen: <strong>{{ strtoupper($purchaseOrder->status) }}</strong>
    </div>

</body>
</html>
