@extends('layouts.app')

@section('title', isset($purchaseOrder) ? 'Edit Purchase Order' : 'Buat Purchase Order')
@section('page-title', isset($purchaseOrder) ? 'Edit Purchase Order' : 'Buat Purchase Order')
@section('page-subtitle', isset($purchaseOrder) ? 'Edit draft PO ' . $purchaseOrder->code : 'Buat PO baru untuk pengadaan barang dari supplier.')

@section('header-actions')
    <a href="{{ route('purchase-orders.index') }}" class="btn btn--secondary">← Kembali</a>
@endsection

@section('content')

<form method="POST"
      action="{{ isset($purchaseOrder) ? route('purchase-orders.update', $purchaseOrder) : route('purchase-orders.store') }}"
      x-data="{
          items: {{ isset($purchaseOrder)
              ? $purchaseOrder->items->map(fn($i) => [
                  'product_id' => $i->product_id,
                  'qty'        => $i->qty_ordered,
                  'buy_price'  => $i->buy_price,
                  'unit'       => $i->product->unit ?? '',
                  'subtotal'   => $i->subtotal,
              ])->toJson()
              : '[]' }},
          products: {{ $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'unit' => $p->unit, 'buy_price' => $p->buy_price])->toJson() }},
          addItem() {
              this.items.push({ product_id: '', qty: 1, buy_price: 0, unit: '', subtotal: 0 });
          },
          removeItem(i) {
              this.items.splice(i, 1);
          },
          onProductChange(i) {
              const p = this.products.find(p => p.id == this.items[i].product_id);
              if (p) {
                  this.items[i].buy_price = p.buy_price;
                  this.items[i].unit      = p.unit;
                  this.recalc(i);
              }
          },
          recalc(i) {
              this.items[i].subtotal = this.items[i].qty * this.items[i].buy_price;
          },
          get total() {
              return this.items.reduce((s, i) => s + (i.subtotal || 0), 0);
          }
      }">
    @csrf
    @if(isset($purchaseOrder))
        @method('PUT')
    @endif

    <div class="card-grid card-grid--2" style="align-items:start">

        {{-- Kolom kiri: info PO --}}
        {{-- Kolom kiri: info PO --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Informasi PO</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:14px">

                <div class="form-group">
                    <label class="form-label">Supplier <span style="color:var(--red-500)">*</span></label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">— Pilih Supplier —</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}"
                                {{ (old('supplier_id', $purchaseOrder->supplier_id ?? '') == $s->id) ? 'selected' : '' }}>
                                {{ $s->name }}
                                @if($s->category) ({{ $s->category }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Diharapkan Tiba</label>
                    <input type="date" name="expected_at" class="form-input"
                        value="{{ old('expected_at', isset($purchaseOrder) ? optional($purchaseOrder->expected_at)->format('Y-m-d') : '') }}"
                        min="{{ now()->format('Y-m-d') }}">
                    @error('expected_at')
                        <span style="font-size:12px; color:var(--red-500)">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-input" rows="3"
                            placeholder="Catatan tambahan untuk supplier...">{{ old('notes', $purchaseOrder->notes ?? '') }}</textarea>
                </div>

                {{-- Total --}}
                <div style="padding:14px; background:var(--border-light); border-radius:var(--radius); margin-top:4px">
                    <div style="display:flex; justify-content:space-between; align-items:center">
                        <span style="font-size:13px; font-weight:600">Total Nilai PO</span>
                        <span style="font-size:18px; font-weight:700; color:var(--blue-600)"
                              x-text="'Rp ' + total.toLocaleString('id-ID')">Rp 0</span>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary" style="justify-content:center; padding:11px"
                        :disabled="items.length === 0">
                    {{ isset($purchaseOrder) ? 'Simpan Perubahan' : 'Simpan sebagai Draft' }}
                </button>

            </div>
        </div>

        {{-- Kolom kanan: items --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Item Pesanan</div>
                <button type="button" class="btn btn--secondary" style="font-size:12px; padding:5px 12px"
                        @click="addItem()">
                    + Tambah Item
                </button>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:12px">

                <template x-if="items.length === 0">
                    <div style="text-align:center; padding:32px; color:var(--text-muted); font-size:13px">
                        Belum ada item. Klik "+ Tambah Item" untuk mulai.
                    </div>
                </template>

                <template x-for="(item, i) in items" :key="i">
                    <div style="padding:14px; border:1px solid var(--border); border-radius:var(--radius);
                                display:flex; flex-direction:column; gap:10px">

                        {{-- Produk --}}
                        <div class="form-group" style="margin:0">
                            <label class="form-label" style="font-size:11.5px">Produk</label>
                            <select class="form-select" :name="`items[${i}][product_id]`"
                                    x-model="item.product_id" @change="onProductChange(i)" required>
                                <option value="">— Pilih Produk —</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">

                            {{-- Qty --}}
                            <div class="form-group" style="margin:0">
                                <label class="form-label" style="font-size:11.5px">
                                    Qty <span x-show="item.unit" x-text="'(' + item.unit + ')'"></span>
                                </label>
                                <input type="number" class="form-input" :name="`items[${i}][qty]`"
                                       x-model.number="item.qty" min="1"
                                       @input="recalc(i)" required>
                            </div>

                            {{-- Harga Beli --}}
                            <div class="form-group" style="margin:0">
                                <label class="form-label" style="font-size:11.5px">Harga Beli (Rp)</label>
                                <input type="number" class="form-input" :name="`items[${i}][buy_price]`"
                                       x-model.number="item.buy_price" min="0"
                                       @input="recalc(i)" required>
                            </div>

                        </div>

                        {{-- Subtotal + hapus --}}
                        <div style="display:flex; justify-content:space-between; align-items:center">
                            <span style="font-size:12.5px; color:var(--text-muted)">
                                Subtotal:
                                <strong style="color:var(--text-primary)"
                                        x-text="'Rp ' + (item.subtotal || 0).toLocaleString('id-ID')"></strong>
                            </span>
                            <button type="button" class="btn btn--danger"
                                    style="padding:4px 10px; font-size:12px"
                                    @click="removeItem(i)">
                                Hapus
                            </button>
                        </div>

                    </div>
                </template>

            </div>
        </div>

    </div>

</form>

@endsection