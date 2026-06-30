@extends('layouts.app')

@section('title', 'Detail Transaksi')
@section('page-title', 'Detail Transaksi')
@section('page-subtitle', 'Melihat rincian lengkap dari transaksi.')

@section('header-actions')
    <a href="{{ route('transactions.index') }}" class="btn btn--secondary btn--sm" style="display: inline-flex; align-items: center; gap: 6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Kembali ke Daftar
    </a>
@endsection

@section('content')
<div style="max-width: 680px; margin: 0 auto;">
    @if(session('success'))
        <div class="alert alert--success" style="margin-bottom:16px">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert--error" style="margin-bottom:16px">{{ session('error') }}</div>
    @endif

    <div class="card" style="padding: 24px;">
        {{-- Header Status --}}
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <span class="badge {{ $transaction->status === 'completed' ? 'badge--green' : 'badge--red' }}" style="font-size: 11px; text-transform: uppercase;">
                    {{ $transaction->status }}
                </span>
                <h2 style="font-size: 18px; font-weight: 700; font-family: var(--font-mono); margin-top: 6px; letter-spacing: -0.5px; color: var(--text-primary);">
                    {{ $transaction->code }}
                </h2>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Metode Pembayaran</span>
                <span class="badge badge--blue" style="font-size: 12px;">{{ $transaction->payment_method }}</span>
            </div>
        </div>

        {{-- Meta Info --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px; margin-bottom: 20px; font-size: 13px;">
            <div>
                <div style="color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; margin-bottom: 4px;">Waktu Transaksi</div>
                <div style="font-weight: 600; color: var(--text-primary);">{{ $transaction->created_at->format('d M Y • H:i:s') }}</div>
            </div>
            <div>
                <div style="color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; margin-bottom: 4px;">Kasir Penanggung Jawab</div>
                <div style="font-weight: 600; color: var(--text-primary);">{{ $transaction->cashier->name ?? 'Kasir' }}</div>
            </div>
        </div>

        {{-- Item list --}}
        <div style="margin-bottom: 24px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;">Daftar Barang Belanja</div>
            
            <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 4px 16px; font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid var(--border); padding-bottom: 6px; margin-bottom: 8px;">
                <span>Nama Item</span>
                <span style="text-align: center; width: 40px;">Qty</span>
                <span style="text-align: right; width: 100px;">Harga Satuan</span>
                <span style="text-align: right; width: 110px;">Subtotal</span>
            </div>

            @foreach($transaction->items as $item)
            <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 8px 16px; font-size: 13px; padding: 10px 0; border-bottom: 1px solid var(--border-light); align-items: center;">
                <div>
                    <span style="font-weight: 600; color: var(--text-primary);">{{ $item->product->name ?? 'Produk Dihapus' }}</span>
                </div>
                <div style="text-align: center; width: 40px; color: var(--text-secondary); font-weight: 500;">
                    {{ $item->qty }} {{ $item->unit ?? 'pcs' }}
                </div>
                <div style="text-align: right; width: 100px; color: var(--text-secondary);">
                    Rp {{ number_format($item->price, 0, ',', '.') }}
                </div>
                <div style="text-align: right; width: 110px; font-weight: 600; color: var(--text-primary);">
                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                </div>
            </div>
            @endforeach
        </div>

        {{-- Summary calculation --}}
        <div style="background: var(--border-light); padding: 16px; border-radius: var(--radius); display: flex; flex-direction: column; gap: 8px; font-size: 13px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; color: var(--text-secondary);">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 15px; border-top: 1px solid var(--border); padding-top: 8px; margin-top: 4px;">
                <span>Total Belanja</span>
                <span style="color: var(--blue-600);">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
            </div>
            
            @if($transaction->payment_method === 'Tunai')
            <div style="display: flex; justify-content: space-between; color: var(--text-secondary); border-top: 1px dashed var(--border); padding-top: 8px; margin-top: 4px;">
                <span>Bayar Tunai</span>
                <span>Rp {{ number_format($transaction->amount_paid, 0, ',', '.') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; color: #16A34A; font-weight: 700;">
                <span>Kembalian</span>
                <span>Rp {{ number_format($transaction->change, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div style="display: flex; gap: 12px; border-top: 1px solid var(--border); padding-top: 16px;">
            <button class="btn btn--secondary" 
                    style="flex: 1; justify-content: center; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;"
                    onclick="window.open('{{ route('transactions.receipt', $transaction->id) }}', '_blank', 'width=420,height=700,scrollbars=yes')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Cetak Struk
            </button>
            
            @if(auth()->user()->role === 'admin' && $transaction->status === 'completed')
            <form method="POST" action="{{ route('transactions.void', $transaction->id) }}" style="display: inline; flex: 1;" onsubmit="return confirm('Apakah Anda yakin ingin melakukan Void (pembatalan) pada transaksi ini? Tindakan ini akan mengembalikan stok barang.')">
                @csrf
                <button type="submit" class="btn btn--danger" style="font-size: 13px; width: 100%; justify-content: center; display: inline-flex; align-items: center; gap: 6px; height: 100%;">
                    Void Transaksi
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
