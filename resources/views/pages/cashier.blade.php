@extends('layouts.app')

@section('title', 'New Transaction')
@section('page-title', 'New Transaction')
@section('page-subtitle', 'Add items to cart and process payment.')

@section('header-actions')
    <a href="{{ route('transactions.index') }}" class="btn btn--secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Back to Transactions
    </a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert--error" style="margin-bottom:16px">
        {{ $errors->first() }}
    </div>
@endif

<div
    x-data="cashierApp({{ $products->toJson() }}, {{ $paymentMethods->toJson() }})"
    style="display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start"
>

    {{-- ===== KIRI: PRODUCT SEARCH + CART ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px">

        {{-- Search produk --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Add Items</div>
                <span x-show="cart.length > 0" class="badge badge--blue" x-text="cart.length + ' item(s)'"></span>
            </div>
            <div class="card-body" style="padding-top:0">
                <div class="search-input-wrapper" style="width:100%; margin-bottom:12px">
                    <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input
                        type="text"
                        class="form-input"
                        placeholder="Search product by name or SKU..."
                        x-model="search"
                        style="width:100%"
                    >
                </div>

                {{-- Product grid --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:10px; max-height:420px; overflow-y:auto">
                    <template x-for="p in filteredProducts" :key="p.id">
                        <div
                            @click="p.qty > 0 && addToCart(p)"
                            :class="p.qty === 0 ? 'product-tile product-tile--disabled' : 'product-tile'"
                            :title="p.qty === 0 ? 'Out of stock' : 'Click to add'"
                        >
                            <div class="product-tile__name" x-text="p.name"></div>
                            <div class="product-tile__sku" x-text="p.sku"></div>
                            <div class="product-tile__price" x-text="'Rp ' + formatNumber(p.sell_price)"></div>
                            <div style="margin-top:6px">
                                <span
                                    :class="p.qty === 0 ? 'badge badge--red' : (p.qty <= p.threshold ? 'badge badge--amber' : 'badge badge--green')"
                                    x-text="p.qty === 0 ? 'Out of Stock' : 'Stock: ' + p.qty"
                                ></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredProducts.length === 0" style="grid-column:1/-1; text-align:center; padding:32px; color:var(--text-muted); font-size:13px">
                        No products found.
                    </div>
                </div>
            </div>
        </div>

        {{-- Cart table --}}
        <div class="card" x-show="cart.length > 0" x-transition>
            <div class="card-header">
                <div class="card-title">Cart</div>
                <button @click="clearCart()" class="btn btn--secondary btn--sm" style="color:var(--red-500)">Clear All</button>
            </div>
            <div class="data-table-wrapper" style="border:none; border-radius:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align:center">Qty</th>
                            <th style="text-align:right">Price</th>
                            <th style="text-align:right">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in cart" :key="item.id">
                            <tr>
                                <td>
                                    <div style="font-weight:600; font-size:13px" x-text="item.name"></div>
                                    <div style="font-size:11px; color:var(--text-muted)" x-text="item.sku"></div>
                                </td>
                                <td style="text-align:center">
                                    <div style="display:flex; align-items:center; justify-content:center; gap:6px">
                                        <button @click="decrement(index)" class="btn-icon" style="width:24px; height:24px; font-size:16px">−</button>
                                        <input
                                            type="number"
                                            x-model.number="item.qty"
                                            @change="item.qty = Math.max(1, Math.min(item.qty, item.stock)); recalc()"
                                            class="form-input"
                                            style="width:52px; text-align:center; padding:4px 6px; font-size:13px"
                                            min="1"
                                            :max="item.stock"
                                        >
                                        <button @click="increment(index)" class="btn-icon" style="width:24px; height:24px; font-size:16px">+</button>
                                    </div>
                                    <div style="font-size:10px; color:var(--text-muted); margin-top:3px" x-text="'max ' + item.stock"></div>
                                </td>
                                <td style="text-align:right; color:var(--text-secondary); font-size:13px" x-text="'Rp ' + formatNumber(item.price)"></td>
                                <td style="text-align:right; font-weight:600; font-size:13px" x-text="'Rp ' + formatNumber(item.qty * item.price)"></td>
                                <td style="text-align:center">
                                    <button @click="removeFromCart(index)" class="btn-icon" title="Remove">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ===== KANAN: SUMMARY + PAYMENT ===== --}}
    <div style="position:sticky; top:20px; display:flex; flex-direction:column; gap:16px">

        {{-- Order Summary --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Order Summary</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:10px">

                {{-- Empty state --}}
                <div x-show="cart.length === 0" style="text-align:center; padding:24px 0; color:var(--text-muted); font-size:13px">
                    No items in cart yet.
                </div>

                {{-- Item list ringkas --}}
                <template x-for="item in cart" :key="item.id">
                    <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-secondary)">
                        <span x-text="item.name + ' ×' + item.qty" style="flex:1; margin-right:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"></span>
                        <span style="font-weight:600; flex-shrink:0" x-text="'Rp ' + formatNumber(item.qty * item.price)"></span>
                    </div>
                </template>

                <div style="border-top:1px solid var(--border-light); margin:4px 0"></div>

                {{-- Total --}}
                <div style="display:flex; justify-content:space-between; align-items:center">
                    <span style="font-size:14px; font-weight:600">Total</span>
                    <span style="font-size:20px; font-weight:800; color:var(--blue-600)" x-text="'Rp ' + formatNumber(total)"></span>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Payment</div>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:14px">

                {{-- Metode --}}
                <div>
                    <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">Payment Method</label>
                    <div style="display:flex; flex-direction:column; gap:6px">
                        <template x-for="pm in paymentMethods" :key="pm.id">
                            <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border-light); border-radius:var(--radius); cursor:pointer"
                                   :style="selectedMethod === pm.name ? 'border-color:var(--blue-600); background:#EFF6FF' : ''">
                                <input type="radio" :value="pm.name" x-model="selectedMethod" style="accent-color:var(--blue-600)">
                                <div>
                                    <div style="font-size:13px; font-weight:600" x-text="pm.name"></div>
                                    <div style="font-size:11px; color:var(--text-muted)" x-text="pm.mdr_fee > 0 ? 'MDR ' + pm.mdr_fee + '%' : 'No fee'"></div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Nominal bayar (cash only) --}}
                <div x-show="isCash">
                    <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">Amount Paid</label>
                    <div class="search-input-wrapper">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:12px; font-weight:600; color:var(--text-muted)">Rp</span>
                        <input
                            type="number"
                            x-model.number="amountPaid"
                            @input="recalc()"
                            class="form-input"
                            style="padding-left:36px; width:100%"
                            placeholder="0"
                            min="0"
                        >
                    </div>

                    {{-- Shortcut nominal --}}
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px">
                        <template x-for="n in quickAmounts" :key="n">
                            <button
                                @click="amountPaid = n; recalc()"
                                class="btn btn--secondary btn--sm"
                                x-text="'Rp ' + formatNumber(n)"
                            ></button>
                        </template>
                    </div>
                </div>

                {{-- Kembalian --}}
                <div x-show="isCash && amountPaid >= total && total > 0"
                     style="padding:10px 12px; background:#F0FDF4; border:1px solid #BBF7D0; border-radius:var(--radius)">
                    <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#166534; margin-bottom:2px">Change</div>
                    <div style="font-size:18px; font-weight:800; color:#16A34A" x-text="'Rp ' + formatNumber(change)"></div>
                </div>

                {{-- Kurang bayar warning --}}
                <div x-show="isCash && amountPaid > 0 && amountPaid < total"
                     style="padding:10px 12px; background:#FEF2F2; border:1px solid #FECACA; border-radius:var(--radius)">
                    <div style="font-size:12px; font-weight:600; color:#DC2626" x-text="'Kurang Rp ' + formatNumber(total - amountPaid)"></div>
                </div>

                {{-- Submit --}}
                <form method="POST" action="{{ route('transactions.store') }}" @submit.prevent="submitTransaction($event)">
                    @csrf
                    <input type="hidden" name="payment_method" :value="selectedMethod">
                    <input type="hidden" name="amount_paid" :value="isCash ? amountPaid : total">
                    <div id="cart-inputs"></div>

                    <button
                        type="submit"
                        class="btn btn--primary"
                        style="width:100%; justify-content:center; padding:12px; font-size:14px"
                        :disabled="!canSubmit"
                        :style="!canSubmit ? 'opacity:0.5; cursor:not-allowed' : ''"
                    >
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        Process Transaction
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
function cashierApp(products, paymentMethods) {
    return {
        products,
        paymentMethods,
        search: '',
        cart: [],
        selectedMethod: paymentMethods.length ? paymentMethods[0].name : '',
        amountPaid: 0,
        total: 0,
        change: 0,
        submitting: false,
        errorMessage: '',

        get filteredProducts() {
            if (!this.search) return this.products;
            const q = this.search.toLowerCase();
            return this.products.filter(p =>
                p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)
            );
        },

        get isCash() {
            const pm = this.paymentMethods.find(p => p.name === this.selectedMethod);
            return pm ? pm.type === 'cash' : false;
        },

        get canSubmit() {
            if (this.cart.length === 0) return false;
            if (!this.selectedMethod) return false;
            if (this.isCash && this.amountPaid < this.total) return false;
            if (this.submitting) return false;
            return true;
        },

        get quickAmounts() {
            if (this.total <= 0) return [];
            const base = Math.ceil(this.total / 1000) * 1000;
            const opts = [base, base + 5000, base + 10000, base + 20000];
            return [...new Set(opts)].slice(0, 4);
        },

        addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            if (existing) {
                if (existing.qty < existing.stock) {
                    existing.qty++;
                    this.recalc();
                }
                return;
            }
            this.cart.push({
                id:    product.id,
                name:  product.name,
                sku:   product.sku,
                price: product.sell_price,
                stock: product.qty,
                qty:   1,
            });
            this.recalc();
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.recalc();
        },

        increment(index) {
            const item = this.cart[index];
            if (item.qty < item.stock) { item.qty++; this.recalc(); }
        },

        decrement(index) {
            const item = this.cart[index];
            if (item.qty > 1) { item.qty--; this.recalc(); }
            else this.removeFromCart(index);
        },

        clearCart() {
            this.cart = [];
            this.amountPaid = 0;
            this.recalc();
        },

        recalc() {
            this.total = this.cart.reduce((sum, i) => sum + i.price * i.qty, 0);
            this.change = Math.max(0, this.amountPaid - this.total);
        },

        formatNumber(n) {
            return Number(n).toLocaleString('id-ID');
        },

        async submitTransaction() {
            if (!this.canSubmit) return;
            this.submitting = true;
            this.errorMessage = '';

            const payload = {
                payment_method: this.selectedMethod,
                amount_paid:    this.isCash ? this.amountPaid : this.total,
                items:          this.cart.map(i => ({ id: i.id, qty: i.qty })),
            };

            try {
                const res = await fetch('{{ route('transactions.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    this.errorMessage = data.message || 'Terjadi kesalahan.';
                    this.submitting = false;
                }
            } catch (e) {
                this.errorMessage = 'Gagal menghubungi server.';
                this.submitting = false;
            }
        },
    }
}
</script>
@endpush