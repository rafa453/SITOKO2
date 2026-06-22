@extends('layouts.app')

@section('title', isset($product) ? 'Edit Product' : 'Add Product')
@section('page-title', isset($product) ? 'Edit Product' : 'Add Product')
@section('page-subtitle', isset($product) ? 'Update product details.' : 'Add a new product to inventory.')

@section('header-actions')
    <a href="{{ route('inventory.index') }}" class="btn btn--secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Back to Inventory
    </a>
@endsection

@section('content')

@if($errors->any())
    <div class="alert alert--error" style="margin-bottom:16px">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST"
      action="{{ isset($product) ? route('inventory.update', $product->id) : route('inventory.store') }}">
    @csrf
    @if(isset($product))
        @method('PUT')
    @endif

    <div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start">

        {{-- ===== KIRI: DETAIL PRODUK ===== --}}
        <div style="display:flex; flex-direction:column; gap:16px">

            {{-- Informasi Dasar --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Basic Information</div>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px">

                    {{-- Nama --}}
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            Product Name <span style="color:var(--red-500)">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            class="form-input"
                            style="width:100%"
                            placeholder="e.g. Beras Premium 5kg"
                            value="{{ old('name', $product->name ?? '') }}"
                            required
                        >
                    </div>

                    {{-- SKU --}}
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            SKU <span style="color:var(--red-500)">*</span>
                        </label>
                        <input
                            type="text"
                            name="sku"
                            class="form-input"
                            style="width:100%"
                            placeholder="e.g. BR-001"
                            value="{{ old('sku', $product->sku ?? '') }}"
                            required
                        >
                        <p style="font-size:11px; color:var(--text-muted); margin-top:4px">Must be unique across all products.</p>
                    </div>

                    {{-- Kategori + Satuan --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                        <div>
                            <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                                Category <span style="color:var(--red-500)">*</span>
                            </label>
                            <input
                                type="text"
                                name="category"
                                class="form-input"
                                style="width:100%"
                                placeholder="e.g. Beras & Tepung"
                                value="{{ old('category', $product->category ?? '') }}"
                                list="category-list"
                                required
                            >
                            <datalist id="category-list">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                                Unit <span style="color:var(--red-500)">*</span>
                            </label>
                            <input
                                type="text"
                                name="unit"
                                class="form-input"
                                style="width:100%"
                                placeholder="e.g. Karung, Pcs, Botol"
                                value="{{ old('unit', $product->unit ?? '') }}"
                                list="unit-list"
                                required
                            >
                            <datalist id="unit-list">
                                <option value="Pcs">
                                <option value="Karung">
                                <option value="Botol">
                                <option value="Kotak">
                                <option value="Kg">
                                <option value="Liter">
                            </datalist>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Harga --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Pricing</div>
                </div>
                <div class="card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            Buy Price (Rp) <span style="color:var(--red-500)">*</span>
                        </label>
                        <input
                            type="number"
                            name="buy_price"
                            class="form-input"
                            style="width:100%"
                            placeholder="0"
                            value="{{ old('buy_price', $product->buy_price ?? '') }}"
                            min="0"
                            required
                        >
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            Sell Price (Rp) <span style="color:var(--red-500)">*</span>
                        </label>
                        <input
                            type="number"
                            name="sell_price"
                            class="form-input"
                            style="width:100%"
                            placeholder="0"
                            value="{{ old('sell_price', $product->sell_price ?? '') }}"
                            min="0"
                            required
                        >
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== KANAN: STOK + ACTIONS ===== --}}
        <div style="position:sticky; top:20px; display:flex; flex-direction:column; gap:16px">

            {{-- Stok --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Stock</div>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:14px">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            Current Stock <span style="color:var(--red-500)">*</span>
                        </label>
                        <input
                            type="number"
                            name="qty"
                            class="form-input"
                            style="width:100%"
                            placeholder="0"
                            value="{{ old('qty', $product->qty ?? 0) }}"
                            min="0"
                            required
                        >
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            Low Stock Threshold <span style="color:var(--red-500)">*</span>
                        </label>
                        <input
                            type="number"
                            name="threshold"
                            class="form-input"
                            style="width:100%"
                            placeholder="10"
                            value="{{ old('threshold', $product->threshold ?? 10) }}"
                            min="0"
                            required
                        >
                        <p style="font-size:11px; color:var(--text-muted); margin-top:4px">
                            Alert will trigger when stock drops to or below this number.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-body" style="display:flex; flex-direction:column; gap:10px">
                    <button type="submit" class="btn btn--primary" style="width:100%; justify-content:center; padding:11px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        {{ isset($product) ? 'Update Product' : 'Save Product' }}
                    </button>
                    <a href="{{ route('inventory.index') }}"
                       class="btn btn--secondary"
                       style="width:100%; justify-content:center; padding:11px">
                        Cancel
                    </a>

                    @if(isset($product))
                    <div style="border-top:1px solid var(--border-light); padding-top:10px; margin-top:4px">
                        <form method="POST" action="{{ route('inventory.destroy', $product->id) }}"
                              onsubmit="return confirm('Hapus produk ini? Tindakan tidak bisa dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger"
                                    style="width:100%; justify-content:center; padding:11px; font-size:12.5px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14H6L5 6"/>
                                    <path d="M10 11v6M14 11v6"/>
                                    <path d="M9 6V4h6v2"/>
                                </svg>
                                Delete Product
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</form>

@endsection