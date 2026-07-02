@extends('layouts.app')

@section('title', 'Brands Management')
@section('page-title', 'Brands')
@section('page-subtitle', 'Manage product brands and their assigned supplier.')

@section('content')

@php
    $totalBrands = $brands->total();
    $brandsWithSupplier = $brands->filter(fn($b) => $b->suppliers->isNotEmpty())->count();
    $brandsWithoutSupplier = $totalBrands - $brandsWithSupplier;
    $totalProducts = $brands->sum('products_count');
@endphp

{{-- STAT CARDS --}}
<div class="stats-grid stats-grid--4" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Brands</span>
        </div>
        <div class="stat-card__value">{{ $totalBrands }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Brands w/ Supplier</span>
            <span class="status-dot status-dot--green"></span>
        </div>
        <div class="stat-card__value">{{ $brandsWithSupplier }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Needs Supplier</span>
            <span class="status-dot status-dot--red"></span>
        </div>
        <div class="stat-card__value" style="color:var(--red-600)">{{ $brandsWithoutSupplier }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <span class="stat-card__label">Products Listed</span>
        </div>
        <div class="stat-card__value">{{ $totalProducts }}</div>
    </div>
</div>

<div style="display:grid; grid-template-columns:3fr 1fr; gap:20px; align-items:start;">
    
    {{-- DATA TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Brand Directory</div>
        </div>
        <div class="data-table-wrapper" style="border:none; border-radius:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Brand Name</th>
                        <th>Supplier Assignment</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                    <tr x-data="{ editSupplier: false }">
                        <td style="font-weight:600;">{{ $brand->name }}</td>
                        <td>
                            @if($brand->suppliers->isNotEmpty())
                                <div x-show="!editSupplier" style="display:flex; gap:8px; align-items:center;">
                                    <span class="badge badge--blue">{{ $brand->suppliers->first()->name }}</span>
                                    <button type="button" @click="editSupplier = true" class="btn btn--secondary btn--sm" style="padding: 2px 8px; font-size:11px;">Ganti</button>
                                </div>
                                <form x-show="editSupplier" method="POST" action="{{ route('brands.assign-supplier', $brand) }}" style="display:flex; gap:6px; align-items:center; display:none;">
                                    @csrf @method('PATCH')
                                    @if($suppliers->isEmpty())
                                        <span style="font-size:12px; color:var(--text-muted)">Belum ada supplier aktif</span>
                                    @else
                                        <select name="supplier_id" class="form-select" style="width:auto; padding: 4px 28px 4px 8px; font-size:12px;">
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" {{ $brand->suppliers->first()->id === $sup->id ? 'selected' : '' }}>
                                                    {{ $sup->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn--primary btn--sm" style="padding: 4px 8px;">Simpan</button>
                                        <button type="button" @click="editSupplier = false" class="btn btn--secondary btn--sm" style="padding: 4px 8px;">Batal</button>
                                    @endif
                                </form>
                            @else
                                <form method="POST" action="{{ route('brands.assign-supplier', $brand) }}" style="display:flex; gap:6px; align-items:center">
                                    @csrf @method('PATCH')
                                    @if($suppliers->isEmpty())
                                        <span style="font-size:12px; color:var(--text-muted)">Belum ada supplier aktif</span>
                                    @else
                                        <select name="supplier_id" class="form-select" style="width:auto; padding: 4px 28px 4px 8px; font-size:12px;" required>
                                            <option value="">-- Pilih Supplier --</option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn--warning btn--sm" style="padding: 4px 10px; border-color:var(--amber-500); color:var(--amber-700)">Assign</button>
                                    @endif
                                </form>
                            @endif
                        </td>
                        <td>{{ $brand->products_count }} item(s)</td>
                        <td>
                            <form method="POST" action="{{ route('brands.destroy', $brand) }}" onsubmit="return confirm('Hapus brand ini?');" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm" style="padding: 4px 8px;" {{ $brand->products_count > 0 ? 'disabled' : '' }} title="{{ $brand->products_count > 0 ? 'Tidak bisa menghapus brand yang memiliki produk' : 'Hapus' }}">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted)">Belum ada brand terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ADD BRAND FORM --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Add New Brand</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('brands.store') }}" style="display:flex; flex-direction:column; gap:12px;">
                @csrf
                <div class="form-group">
                    <label class="form-label">Brand Name</label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. Indomie">
                </div>
                <button type="submit" class="btn btn--primary" style="justify-content:center;">Save Brand</button>
            </form>
        </div>
    </div>
</div>

@endsection
