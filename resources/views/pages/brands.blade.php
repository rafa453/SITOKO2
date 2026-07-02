@extends('layouts.app')

@section('title', 'Brands Management')
@section('page-title', 'Brands')
@section('page-subtitle', 'Manage product brands and their assigned supplier.')

@section('header-actions')
    <button type="button" @click="$dispatch('open-add-brand-modal')" class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Brand
    </button>
@endsection

@section('content')

@php
    $totalBrands = $brands->total();
    $brandsWithSupplier = $brands->filter(fn($b) => $b->suppliers->isNotEmpty())->count();
    $brandsWithoutSupplier = $totalBrands - $brandsWithSupplier;
    $totalProducts = $brands->sum('products_count');
@endphp

{{-- STAT CARDS --}}
@push('styles')
<style>
.stat-card-interactive {
    cursor: pointer;
    transition: all 0.2s ease;
}
.stat-card-interactive:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
@endpush

<div x-data="{ isModalOpen: false, modalTitle: '', modalType: '' }">
    <div class="stats-grid stats-grid--4" style="margin-bottom: 24px;">
        <div class="stat-card stat-card-interactive" @click="isModalOpen = true; modalTitle = 'Total Brands'; modalType = 'total'">
            <div class="stat-card__header">
                <span class="stat-card__label">Total Brands</span>
            </div>
            <div class="stat-card__value">{{ $totalBrands }}</div>
        </div>
        <div class="stat-card stat-card-interactive" @click="isModalOpen = true; modalTitle = 'Brands with Supplier'; modalType = 'with_supplier'">
            <div class="stat-card__header">
                <span class="stat-card__label">Brands w/ Supplier</span>
                <span class="status-dot status-dot--green"></span>
            </div>
            <div class="stat-card__value">{{ $brandsWithSupplier }}</div>
        </div>
        <div class="stat-card stat-card-interactive" @click="isModalOpen = true; modalTitle = 'Brands without Supplier'; modalType = 'needs_supplier'">
            <div class="stat-card__header">
                <span class="stat-card__label">Needs Supplier</span>
                <span class="status-dot status-dot--red"></span>
            </div>
            <div class="stat-card__value" style="color:var(--red-600)">{{ $brandsWithoutSupplier }}</div>
        </div>
        <div class="stat-card stat-card-interactive" @click="isModalOpen = true; modalTitle = 'Total Products Listed'; modalType = 'products'">
            <div class="stat-card__header">
                <span class="stat-card__label">Products Listed</span>
            </div>
            <div class="stat-card__value">{{ $totalProducts }}</div>
        </div>
    </div>

    {{-- STAT DETAIL MODAL --}}
    <div x-show="isModalOpen" 
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1050; align-items:center; justify-content:center; padding:20px;"
         x-transition.opacity>
        
        <div @click.outside="isModalOpen = false" 
             style="background:#fff; border-radius:12px; width:100%; max-width:500px; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);" 
             x-transition>
            
            {{-- Modal Header --}}
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--border-light);">
                <div style="font-weight:700; font-size:16px; color:var(--text-primary)" x-text="modalTitle"></div>
                <button type="button" @click="isModalOpen = false" style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding:4px; display:flex; align-items:center; justify-content:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            
            {{-- Modal Body --}}
            <div style="padding:0; max-height:60vh; overflow-y:auto;">
                <table class="data-table" style="width:100%; border-top:none;">
                    <thead>
                        <tr>
                            <th style="background:#F8FAFC; position:sticky; top:0;">Brand Name</th>
                            <th style="background:#F8FAFC; position:sticky; top:0;">Supplier</th>
                            <th style="background:#F8FAFC; position:sticky; top:0;">Products</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                        <tr x-show="
                            modalType === 'total' || 
                            modalType === 'products' || 
                            (modalType === 'with_supplier' && {{ $brand->suppliers->isNotEmpty() ? 'true' : 'false' }}) || 
                            (modalType === 'needs_supplier' && {{ $brand->suppliers->isEmpty() ? 'true' : 'false' }})
                        ">
                            <td style="font-weight:600; color:var(--text-primary)">{{ $brand->name }}</td>
                            <td>
                                @if($brand->suppliers->isNotEmpty())
                                    <span class="badge badge--green">{{ $brand->suppliers->first()->name }}</span>
                                @else
                                    <span class="badge badge--red">Needs Supplier</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $brand->products_count }} item(s)</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:var(--text-muted)">Belum ada data brand.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


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

    {{-- ADD BRAND MODAL --}}
    <div x-data="{ open: false }" 
         @open-add-brand-modal.window="open = true"
         x-show="open" 
         style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.45); z-index:1000; align-items:center; justify-content:center; padding:20px;"
         x-transition.opacity>
        
        <div class="card" @click.outside="open = false" style="width:100%; max-width:420px; margin:0; box-shadow:0 20px 50px rgba(15,23,42,0.25);" x-transition>
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div class="card-title">Add New Brand</div>
                <button type="button" @click="open = false" style="background:none; border:none; cursor:pointer; color:var(--text-muted); display:flex; align-items:center; justify-content:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('brands.store') }}" style="display:flex; flex-direction:column; gap:16px;">
                    @csrf
                    <div>
                        <label class="form-label" style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">
                            Brand Name <span style="color:var(--red-500)">*</span>
                        </label>
                        <input type="text" name="name" class="form-input" style="width:100%" required placeholder="e.g. Indomie, Bimoli">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px">
                        <button type="button" class="btn btn--secondary" @click="open = false">Cancel</button>
                        <button type="submit" class="btn btn--primary">Save Brand</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
