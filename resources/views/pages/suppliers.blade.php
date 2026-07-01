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
        <table class="data-table" style="table-layout:fixed; width:100%">
            <colgroup>
                <col style="width:16%">
                <col style="width:13%">
                <col style="width:20%">
                <col style="width:12%">
                <col style="width:19%">
                <col style="width:8%">
                <col style="width:12%">
            </colgroup>
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
                <tr id="supplier-row-{{ $s->id }}"
                    data-name="{{ $s->name }}"
                    data-category="{{ $s->category ?? '—' }}"
                    data-brand="{{ $s->brand ?? '—' }}"
                    data-phone="{{ $s->phone ?? '—' }}"
                    data-address="{{ $s->address ?? '—' }}"
                    data-status="{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}"
                    data-bank-name="{{ $s->bank_name ?? '—' }}"
                    data-bank-account-number="{{ $s->bank_account_number ?? '—' }}"
                    data-bank-account-holder="{{ $s->bank_account_holder ?? '—' }}">
                    <td style="font-weight:600; white-space:normal; word-break:break-word">{{ $s->name }}</td>
                    <td style="white-space:normal">
                        @if($s->category)
                            <span class="badge badge--blue">{{ $s->category }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-secondary" style="white-space:normal; word-break:break-word">
                        {{ $s->brand ?? '—' }}
                    </td>
                    <td class="text-secondary" style="white-space:nowrap">{{ $s->phone ?? '—' }}</td>
                    <td class="text-secondary"
                        style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis"
                        title="{{ $s->address }}">
                        {{ $s->address ?? '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $s->is_active ? 'badge--green' : 'badge--red' }}">
                            {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center">
                            <button type="button"
                                    class="btn btn--secondary"
                                    style="padding:5px 10px; font-size:12px"
                                    onclick="openSupplierDetail({{ $s->id }})">
                                Detail
                            </button>
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

{{-- Modal Detail Supplier --}}
<div id="supplierDetailOverlay"
     onclick="if(event.target===this) closeSupplierDetail()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1000; align-items:center; justify-content:center; padding:16px">
    <div style="background:var(--bg-card, #fff); border-radius:12px; width:100%; max-width:440px; padding:24px; box-shadow:0 10px 40px rgba(0,0,0,0.25); max-height:85vh; overflow-y:auto">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px">
            <h3 id="sd-name" style="margin:0; font-size:18px; font-weight:700"></h3>
            <button type="button" onclick="closeSupplierDetail()"
                    style="background:none; border:none; cursor:pointer; font-size:20px; line-height:1; color:var(--text-muted, #888)">
                &times;
            </button>
        </div>

        <div style="display:flex; flex-direction:column; gap:14px">
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Status</div>
                <span id="sd-status" class="badge"></span>
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Kategori Produk</div>
                <div id="sd-category"></div>
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Nama Brand</div>
                <div id="sd-brand"></div>
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Nomor Telepon</div>
                <div id="sd-phone"></div>
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Alamat</div>
                <div id="sd-address" style="white-space:normal; word-break:break-word"></div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border-light,#eee); margin:4px 0">

            <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); font-weight:600">
                Informasi Rekening
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Bank</div>
                <div id="sd-bank-name"></div>
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Nomor Rekening</div>
                <div id="sd-bank-account-number" style="font-family:monospace"></div>
            </div>
            <div>
                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted,#888); margin-bottom:4px">Atas Nama</div>
                <div id="sd-bank-account-holder"></div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:20px">
            <button type="button" class="btn btn--secondary" onclick="closeSupplierDetail()">Tutup</button>
        </div>
    </div>
</div>

<script>
    function openSupplierDetail(id) {
        const row = document.getElementById('supplier-row-' + id);
        if (!row) return;

        document.getElementById('sd-name').textContent     = row.dataset.name;
        document.getElementById('sd-category').textContent = row.dataset.category;
        document.getElementById('sd-brand').textContent    = row.dataset.brand;
        document.getElementById('sd-phone').textContent    = row.dataset.phone;
        document.getElementById('sd-address').textContent  = row.dataset.address;

        document.getElementById('sd-bank-name').textContent            = row.dataset.bankName;
        document.getElementById('sd-bank-account-number').textContent  = row.dataset.bankAccountNumber;
        document.getElementById('sd-bank-account-holder').textContent  = row.dataset.bankAccountHolder;

        const statusEl = document.getElementById('sd-status');
        statusEl.textContent = row.dataset.status;
        statusEl.className = 'badge ' + (row.dataset.status === 'Aktif' ? 'badge--green' : 'badge--red');

        document.getElementById('supplierDetailOverlay').style.display = 'flex';
    }

    function closeSupplierDetail() {
        document.getElementById('supplierDetailOverlay').style.display = 'none';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSupplierDetail();
    });
</script>

@endsection