@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-title', 'Suppliers')
@section('page-subtitle', 'Kelola data supplier untuk kebutuhan pengadaan barang.')

@section('header-actions')
    <a href="{{ route('suppliers.create') }}" class="btn btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Supplier
    </a>
@endsection

@section('content')

{{-- Filter --}}
<div class="card" style="margin-bottom:0">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="{{ route('suppliers.index') }}"
              style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama atau kategori..."
                   class="form-input" style="width:240px">
            <select name="status" class="form-select" style="width:140px">
                <option value="">Semua Status</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('suppliers.index') }}" class="btn btn--ghost">Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Supplier</th>
                    <th>Kategori</th>
                    <th>Brand</th>
                    <th>Telepon</th>
                    <th>Alamat</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $s)
                <tr>
                    <td style="font-weight:600">{{ $s->name }}</td>
                    <td>
                        @if($s->category)
                            <span class="badge badge--blue">{{ $s->category }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($s->brand)
                            <span class="badge badge--blue">{{ $s->brand }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-secondary">{{ $s->phone ?? '—' }}</td>
                    <td class="text-secondary" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                        {{ $s->address ?? '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $s->is_active ? 'badge--green' : 'badge--red' }}">
                            {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; align-items:center">
                            <a href="{{ route('suppliers.edit', $s) }}"
                            class="btn btn--secondary" style="padding:5px 10px; font-size:12px">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('suppliers.toggle-active', $s) }}" style="margin:0">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="btn btn--ghost" style="padding:5px 10px; font-size:12px">
                                    {{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            @if(!$s->purchaseOrders()->exists())
                            <form method="POST" action="{{ route('suppliers.destroy', $s) }}" style="margin:0"
                                onsubmit="return confirm('Hapus supplier {{ addslashes($s->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="btn btn--danger" style="padding:5px 10px; font-size:12px">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted)">
                        Belum ada supplier.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())
    <div style="padding:16px 20px; border-top:1px solid var(--border-light)">
        {{ $suppliers->links('vendor.pagination.custom') }}
    </div>
    @endif
</div>

@endsection