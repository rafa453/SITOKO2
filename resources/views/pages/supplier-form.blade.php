@extends('layouts.app')

@section('title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')
@section('page-title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')
@section('page-subtitle', isset($supplier) ? 'Perbarui data supplier.' : 'Tambah supplier baru.')

@section('header-actions')
    <a href="{{ route('suppliers.index') }}" class="btn btn--secondary">
        ← Kembali
    </a>
@endsection

@section('content')

<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST"
              action="{{ isset($supplier) ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
            @csrf
            @if(isset($supplier)) @method('PUT') @endif

            {{-- Nama --}}
            <div class="form-group">
                <label class="form-label">Nama Supplier <span style="color:var(--red-500)">*</span></label>
                <input type="text" name="name" class="form-input @error('name') is-invalid @enderror"
                       value="{{ old('name', $supplier->name ?? '') }}"
                       placeholder="CV Maju Jaya" required>
                @error('name')
                    <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                @enderror
            </div>

            {{-- Kategori --}}
            <div class="form-group">
                <label class="form-label">Kategori Produk</label>
                <select name="category" class="form-input cursor-pointer bg-white">
                    <option value="">Pilih Kategori (Opsional)</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ old('category', $supplier->category ?? '') == $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
                <span style="font-size:11.5px; color:var(--text-muted); margin-top:4px; display:block">
                    Kategori produk yang biasa disupply oleh supplier ini.
                </span>
            </div>

             {{-- Brand --}}
            <div class="form-group">
                <label class="form-label">Nama Brand</label>
                <input type="text" name="brand" class="form-input"
                    value="{{ old('brand', $supplier->brand ?? '') }}"
                    placeholder="Indomie, Aqua, Beras Cap Jago">
                <span style="font-size:11.5px; color:var(--text-muted); margin-top:4px; display:block">
                    Pisahkan dengan koma jika lebih dari satu brand.
                </span>
            </div>

            {{-- Telepon --}}
            <div class="form-group">
                <label class="form-label">Nomor Telepon</label>
                <input type="text" name="phone" class="form-input"
                    value="{{ old('phone', $supplier->phone ?? '') }}"
                    placeholder="0812-xxxx-xxxx">
            </div>

            {{-- Alamat --}}
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-input" rows="3"
                          placeholder="Jl. Contoh No. 1, Bogor">{{ old('address', $supplier->address ?? '') }}</textarea>
            </div>

            {{-- Informasi Rekening --}}
            <div style="border-top:1px solid var(--border-light); margin:20px 0 16px; padding-top:16px">
                <h4 style="margin:0 0 4px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted)">
                    Informasi Rekening
                </h4>
                <p style="margin:0 0 12px; font-size:11.5px; color:var(--text-muted)">
                    Opsional, isi jika pembayaran ke supplier ini dilakukan via transfer bank.
                </p>
            </div>

            {{-- Nama Bank --}}
            <div class="form-group">
                <label class="form-label">Nama Bank</label>
                <input type="text" name="bank_name" class="form-input"
                    value="{{ old('bank_name', $supplier->bank_name ?? '') }}"
                    placeholder="BCA, BRI, Mandiri">
            </div>

            {{-- Nomor Rekening --}}
            <div class="form-group">
                <label class="form-label">Nomor Rekening</label>
                <input type="text" name="bank_account_number" class="form-input"
                    value="{{ old('bank_account_number', $supplier->bank_account_number ?? '') }}"
                    placeholder="1234567890">
            </div>

            {{-- Atas Nama --}}
            <div class="form-group">
                <label class="form-label">Atas Nama</label>
                <input type="text" name="bank_account_holder" class="form-input"
                    value="{{ old('bank_account_holder', $supplier->bank_account_holder ?? '') }}"
                    placeholder="Nama pemilik rekening">
                <span style="font-size:11.5px; color:var(--text-muted); margin-top:4px; display:block">
                    Sering berbeda dari nama usaha/toko.
                </span>
            </div>

            <div style="display:flex; gap:10px; margin-top:8px">
                <button type="submit" class="btn btn--primary">
                    {{ isset($supplier) ? 'Simpan Perubahan' : 'Tambah Supplier' }}
                </button>
                <a href="{{ route('suppliers.index') }}" class="btn btn--ghost">Batal</a>
            </div>

        </form>

        {{-- Form hapus HARUS di luar form edit --}}
        @if(isset($supplier) && !$supplier->purchaseOrders()->exists())
        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
              style="margin-top:10px"
              onsubmit="return confirm('Hapus supplier ini?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn--danger">Hapus Supplier</button>
        </form>
        @endif
    </div>
</div>

@endsection