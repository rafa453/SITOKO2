@extends('layouts.app')

@section('title', 'Buat Retur Supplier')
@section('page-title', 'Buat Retur Supplier')
@section('page-subtitle', 'Kembalikan barang rusak/bermasalah ke supplier dari PO ' . $po->code)

@section('header-actions')
    <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn--secondary">← Kembali ke PO</a>
@endsection

@section('content')

<form method="POST" action="{{ route('supplier-returns.store') }}"
      x-data="{
          items: {{ $returnableItems->map(fn($item) => [
              'po_item_id'  => $item->id,
              'product_id'  => $item->product_id,
              'name'        => $item->product?->name ?? 'Produk Dihapus',
              'unit'        => $item->product?->unit ?? '',
              'buy_price'   => $item->buy_price,
              'qty_received'=> $item->qty_received,
              'qty_returned_before' => $returnedQtys[$item->id] ?? 0,
              'qty_max'     => $item->qty_received - ($returnedQtys[$item->id] ?? 0),
              'qty'         => 0,
              'subtotal'    => 0,
              'checked'     => false,
          ])->toJson() }},
          recalc(i) {
              this.items[i].subtotal = this.items[i].qty * this.items[i].buy_price;
          },
          get total() {
              return this.items.reduce((s, item) => s + (item.checked ? (item.subtotal || 0) : 0), 0);
          },
          get hasItems() {
              return this.items.some(item => item.checked && item.qty > 0);
          }
      }">
    @csrf
    <input type="hidden" name="purchase_order_id" value="{{ $po->id }}">

    <div class="card-grid card-grid--2" style="align-items:start">

        {{-- Kolom kiri --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Informasi Retur</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:14px">

                <div style="padding:12px; background:var(--border-light); border-radius:var(--radius); font-size:13px">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px">
                        <span style="color:var(--text-muted)">Nomor PO</span>
                        <span style="font-weight:600; font-family:monospace">{{ $po->code }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span style="color:var(--text-muted)">Supplier</span>
                        <span style="font-weight:600">{{ $po->supplier->name }}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alasan Retur</label>
                    <textarea name="reason" class="form-input" rows="3"
                              placeholder="Contoh: Barang rusak, kadaluarsa, tidak sesuai pesanan...">{{ old('reason') }}</textarea>
                </div>

                <div style="padding:14px; background:var(--border-light); border-radius:var(--radius)">
                    <div style="display:flex; justify-content:space-between; align-items:center">
                        <span style="font-size:13px; font-weight:600">Total Nilai Retur</span>
                        <span style="font-size:18px; font-weight:700; color:var(--red-500)"
                              x-text="'Rp ' + total.toLocaleString('id-ID')">Rp 0</span>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary" style="justify-content:center; padding:11px"
                        :disabled="!hasItems">
                    Simpan Retur sebagai Draft
                </button>

            </div>
        </div>

        {{-- Kolom kanan: item list --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Pilih Item yang Diretur</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:12px">

                <template x-for="(item, i) in items" :key="item.po_item_id">
                    <div style="padding:14px; border:1px solid var(--border); border-radius:var(--radius);
                                display:flex; flex-direction:column; gap:10px"
                         :style="item.checked ? 'border-color:var(--blue-600); background:var(--blue-50, #eff6ff)' : ''">

                        {{-- Checkbox + nama produk --}}
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer">
                            <input type="checkbox" x-model="item.checked"
                                   style="width:16px; height:16px; cursor:pointer">
                            <span style="font-weight:600; font-size:13px" x-text="item.name"></span>
                            <span style="font-size:11px; color:var(--text-muted)"
                                  x-text="item.unit ? '(' + item.unit + ')' : ''"></span>
                        </label>

                        {{-- Info qty --}}
                        <div style="font-size:12px; color:var(--text-muted); display:flex; gap:16px">
                            <span>Diterima: <strong x-text="item.qty_received"></strong></span>
                            <span>Sudah diretur: <strong x-text="item.qty_returned_before"></strong></span>
                            <span>Maks retur: <strong x-text="item.qty_max"></strong></span>
                        </div>

                        {{-- Input qty (hanya muncul kalau checked) --}}
                        <template x-if="item.checked">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                                <div class="form-group" style="margin:0">
                                    <label class="form-label" style="font-size:11.5px">Qty Diretur</label>
                                    <input type="number" class="form-input"
                                           x-model.number="item.qty"
                                           :max="item.qty_max" min="1"
                                           @input="recalc(i)" required>
                                </div>
                                <div class="form-group" style="margin:0">
                                    <label class="form-label" style="font-size:11.5px">Subtotal</label>
                                    <div style="padding:8px 10px; background:var(--border-light);
                                                border-radius:var(--radius); font-size:13px; font-weight:600"
                                         x-text="'Rp ' + (item.subtotal || 0).toLocaleString('id-ID')">
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Hidden inputs untuk submit --}}
                        <template x-if="item.checked && item.qty > 0">
                            <div>
                                <input type="hidden" :name="`items[${i}][po_item_id]`"   :value="item.po_item_id">
                                <input type="hidden" :name="`items[${i}][product_id]`"   :value="item.product_id">
                                <input type="hidden" :name="`items[${i}][qty_returned]`" :value="item.qty">
                                <input type="hidden" :name="`items[${i}][buy_price]`"    :value="item.buy_price">
                            </div>
                        </template>

                    </div>
                </template>

            </div>
        </div>

    </div>

</form>

@endsection