@extends('layouts.app')

@section('title', 'Payment Methods')
@section('page-title', 'Payment Methods')
@section('page-subtitle', 'Configure and monitor accepted payment channels.')

@section('header-actions')
    <div style="display:flex; align-items:center; gap:12px">
        <form method="GET" action="{{ route('settings.payment-methods') }}" style="display:flex; gap:6px; align-items:center">
            <input type="date" name="date_from" class="form-input" style="width:145px" value="{{ $dateFrom->format('Y-m-d') }}" onchange="this.form.submit()">
            <span style="font-size:12px; color:var(--text-muted)">—</span>
            <input type="date" name="date_to" class="form-input" style="width:145px" value="{{ $dateTo->format('Y-m-d') }}" onchange="this.form.submit()">
        </form>
        <button type="button" class="btn btn--primary" onclick="window.dispatchEvent(new CustomEvent('open-modal', {detail: 'add'}))">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Payment Method
        </button>
    </div>
@endsection

@section('content')

@php
    $dateLabel = $dateFrom->format('d M') . ' - ' . $dateTo->format('d M Y');
    if ($dateFrom->format('Y-m-d') === $dateTo->format('Y-m-d')) {
        $dateLabel = $dateFrom->format('d M Y');
    }
@endphp

@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px; padding:12px 16px; background:#D1FAE5; border-left:4px solid #10B981; border-radius:4px; font-size:13px; color:#065F46">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert--error" style="margin-bottom:16px; padding:12px 16px; background:#FEE2E2; border-left:4px solid #EF4444; border-radius:4px; font-size:13px; color:#991B1B">
        <strong style="display:block; margin-bottom:4px">Gagal menyimpan data:</strong>
        <ul style="margin:0 0 0 16px; padding:0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div x-data="{ openModal: null }" @open-modal.window="openModal = $event.detail">

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid stats-grid--3">

    <div class="stat-card" style="cursor:pointer" @click="openModal = 'active'">
        <div class="stat-card__header">
            <span class="stat-card__label">Active Payment Methods</span>
            <span class="status-dot status-dot--green"></span>
        </div>
        <div class="stat-card__value">{{ $activeCount }}</div>
        <div class="stat-card__meta text-muted text-sm">
            <span class="badge badge--gray">CHANNELS</span>
            All primary gateways operational
        </div>
    </div>

    <div class="stat-card" style="cursor:pointer" @click="openModal = 'digital'">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Digital ({{ $dateLabel }})</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue-600)" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
            </svg>
        </div>
        <div class="stat-card__value">{{ $digitalToday }}</div>
        <div class="stat-card__meta">
            <span style="color:var(--blue-600); font-weight:700; font-size:13px"></span>
        </div>
    </div>

    <div class="stat-card" style="cursor:pointer" @click="openModal = 'cash'">
        <div class="stat-card__header">
            <span class="stat-card__label">Total Cash ({{ $dateLabel }})</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--amber-500)" stroke-width="2">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>
        <div class="stat-card__value">{{ $cashToday }}</div>
        <div class="stat-card__meta">
            <span style="color:var(--amber-700); font-weight:700; font-size:13px"></span>
        </div>
    </div>

</div>

{{-- ===== PAYMENT METHOD CARDS ===== --}}
<div x-data="{ editModal: null }" style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px">
    @foreach($methods as $method)
    @php
        $perf = $performance->firstWhere('id', $method->id);
        $trxCount = $perf->trx_count ?? 0;
        $revenue = $perf->revenue ?? 0;
        $color = $method->type === 'cash' ? '#F59E0B' : '#2563EB';
    @endphp
    <div class="card">
        <div class="card-body">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px">
                <div style="display:flex; align-items:center; gap:10px">
                    <div style="width:36px; height:36px; border-radius:8px; background:{{ $color }}22; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:{{ $color }}">
                        {{ strtoupper(substr($method->name,0,2)) }}
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:14px">{{ $method->name }}</div>
                        <span class="badge {{ $method->type==='cash' ? 'badge--amber' : 'badge--blue' }}" style="font-size:10px">{{ strtoupper($method->type) }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('settings.payment-methods.toggle', $method) }}">
                    @csrf @method('PATCH')
                    <label class="toggle" style="cursor:pointer">
                        <input type="checkbox" onchange="this.form.submit()" {{ $method->is_active ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </form>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px">
                <div>
                    <div style="font-size:10px; font-weight:600; text-transform:uppercase; color:var(--text-muted); letter-spacing:.5px">TRX ({{ $dateLabel }})</div>
                    <div style="font-size:20px; font-weight:800; margin-top:2px">{{ $trxCount }}</div>
                </div>
                <div>
                    <div style="font-size:10px; font-weight:600; text-transform:uppercase; color:var(--text-muted); letter-spacing:.5px">Revenue</div>
                    <div style="font-size:16px; font-weight:800; margin-top:2px">Rp{{ number_format($revenue, 0, ',', '.') }}</div>
                </div>
            </div>

            <div style="font-size:11.5px; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:4px">
            </div>

            <div style="display:flex; gap:6px">
                <button class="btn btn--secondary btn--sm" style="flex:1; justify-content:center"
                    @click="editModal = {{ $method->id }}">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit Settings
                </button>
            </div>
        </div>
    </div>
    @endforeach

    <template x-if="editModal !== null">
        <div style="position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:50; display:flex; align-items:center; justify-content:center"
             @click.self="editModal = null">
            @foreach($methods as $method)
            <div x-show="editModal === {{ $method->id }}" style="background:var(--surface); border-radius:var(--radius); padding:24px; width:420px; max-width:90vw">
                <div style="font-weight:700; font-size:15px; margin-bottom:16px">Edit: {{ $method->name }}</div>
                <form method="POST" action="{{ route('settings.payment-methods.update', $method) }}">
                    @csrf @method('PATCH')
                    <div class="form-group">
                        <label class="form-label">Method Name</label>
                        <input class="form-input" type="text" name="name" value="{{ $method->name }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Method Code</label>
                        <input class="form-input" type="text" name="code" value="{{ $method->code }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="digital" {{ $method->type === 'digital' ? 'selected' : '' }}>Digital</option>
                            <option value="cash" {{ $method->type === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="edc" {{ $method->type === 'edc' ? 'selected' : '' }}>EDC</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Provider</label>
                        <input class="form-input" type="text" name="provider" value="{{ $method->provider }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">MDR Fee (%)</label>
                        <input class="form-input" type="number" name="mdr_fee" value="{{ $method->mdr_fee }}" step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-input" name="notes" rows="2" style="resize:none">{{ $method->notes }}</textarea>
                    </div>
                    <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:16px">
                        <button type="button" class="btn btn--secondary" @click="editModal = null">Cancel</button>
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </template>
</div>

{{-- ===== PERFORMANCE TABLE ===== --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Performance Comparison</div>
    </div>
    <div class="data-table-wrapper" style="border:none; border-radius:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Type</th>
                    <th>Transactions</th>
                    <th>Revenue (Rp)</th>
                    <th>Avg. Value</th>
                    <th>% of Total Revenue</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalRevenue = $performance->sum('revenue');
                    $totalTrx = $performance->sum('trx_count');
                @endphp
                @foreach($performance as $perf)
                @php
                    $bar_color = $perf->type === 'cash' ? '#F59E0B' : '#2563EB';
                    $pct = $totalRevenue > 0 ? round(($perf->revenue / $totalRevenue) * 100) : 0;
                    $avg = $perf->trx_count > 0 ? number_format($perf->revenue / $perf->trx_count, 0, ',', '.') : '-';
                @endphp
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px">
                            <div style="width:20px; height:20px; border-radius:4px; background:{{ $bar_color }}22; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:800; color:{{ $bar_color }}">
                                {{ strtoupper(substr($perf->name,0,2)) }}
                            </div>
                            <span style="font-weight:600">{{ $perf->name }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $perf->type==='cash' ? 'badge--amber' : 'badge--blue' }}">{{ strtoupper($perf->type) }}</span>
                    </td>
                    <td style="font-weight:600">{{ $perf->trx_count }}</td>
                    <td style="font-weight:600">Rp{{ number_format($perf->revenue, 0, ',', '.') }}</td>
                    <td class="text-secondary">{{ $avg }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px">
                            <div class="progress-bar" style="width:80px; flex-shrink:0">
                                <div class="progress-bar__fill" style="width:{{ $pct }}%; background:{{ $bar_color }}"></div>
                            </div>
                            <span style="font-size:12px; font-weight:600">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($perf->is_active)
                        <div style="display:flex; align-items:center; gap:5px"><span class="status-dot status-dot--green"></span><span style="font-size:12.5px; color:var(--green-700); font-weight:600">Active</span></div>
                        @else
                        <div style="display:flex; align-items:center; gap:5px"><span class="status-dot status-dot--red"></span><span style="font-size:12.5px; color:var(--red-700); font-weight:600">Inactive</span></div>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('settings.payment-methods.toggle', $perf) }}" style="margin: 0; display: flex; align-items: center; justify-content: center;">
                            @csrf @method('PATCH')
                            <label class="toggle" style="cursor:pointer; transform: scale(0.9);">
                                <input type="checkbox" onchange="this.form.submit()" {{ $perf->is_active ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </form>
                    </td>
                </tr>
                @endforeach

                {{-- Total row --}}
                <tr style="background:var(--sidebar-bg)">
                    <td colspan="2" style="font-weight:800; color:#fff; font-size:12px; text-transform:uppercase; letter-spacing:.5px">Total Revenue ({{ $dateLabel }})</td>
                    <td style="font-weight:800; color:#fff">{{ $totalTrx }}</td>
                    <td style="font-weight:800; color:#fff">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                    <td style="color:#64748B">-</td>
                    <td colspan="3">
                        <div class="progress-bar" style="background:rgba(255,255,255,.1)">
                            <div class="progress-bar__fill" style="width:100%; background:#2563EB"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ===== 7-DAY TREND ===== --}}
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <div>
            <div class="card-title">7-Day Transaction Trend</div>
            <div class="card-subtitle">Daily Volume by Method</div>
        </div>
        <div style="display:flex; gap:10px; font-size:11.5px; align-items:center">
            <span style="display:flex; align-items:center; gap:4px"><span class="status-dot" style="background:#2563EB"></span> GP</span>
            <span style="display:flex; align-items:center; gap:4px"><span class="status-dot" style="background:#F59E0B"></span> CS</span>
            <span style="display:flex; align-items:center; gap:4px"><span class="status-dot" style="background:#EF4444"></span> QR</span>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-wrapper" style="height:250px">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

{{-- ===== STORE PROFILE ===== --}}
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <div class="card-title">Store Profile</div>
    </div>
    <div class="card-body">
        @php $store = \App\Models\StoreProfile::get(); @endphp
        <form method="POST" action="{{ route('settings.store-profile.update') }}">
            @csrf @method('PATCH')
            <div class="card-grid card-grid--2" style="gap:16px; margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Store Name</label>
                    <input class="form-input" type="text" name="store_name" value="{{ $store->store_name }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Store Subtitle / Slogan</label>
                    <input class="form-input" type="text" name="store_subtitle" value="{{ $store->store_subtitle }}">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Address</label>
                    <input class="form-input" type="text" name="address" value="{{ $store->address }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input class="form-input" type="text" name="phone" value="{{ $store->phone }}">
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input class="form-input" type="text" name="city" value="{{ $store->city }}">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end">
                <button type="submit" class="btn btn--primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>

    {{-- MODAL POPUPS --}}
    <div x-show="openModal" 
         x-transition.opacity
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;"
         @click.self="openModal = null"
         x-cloak>

        <div class="card" 
             x-show="openModal"
             x-transition.scale.85
             style="width: 520px; max-width: 100%; max-height: 85vh; display: flex; flex-direction: column; background: #fff; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); margin: auto;">
            
            {{-- Header --}}
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-light)">
                <div class="card-title" style="font-weight: 700; font-size: 16px;">
                    <span x-show="openModal === 'active'">Active Payment Methods</span>
                    <span x-show="openModal === 'digital'">Digital Methods ({{ $dateLabel }})</span>
                    <span x-show="openModal === 'cash'">Cash Methods ({{ $dateLabel }})</span>
                    <span x-show="openModal === 'add'">Add New Payment Method</span>
                </div>
                <button class="btn-icon" @click="openModal = null" style="background: none; border: none; cursor: pointer; color: var(--text-muted)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div style="overflow-y: auto; flex: 1; padding: 10px 0;">
                <template x-if="openModal === 'active'">
                    <div>
                        @forelse($methods->where('is_active', true) as $m)
                        <div style="padding:12px 20px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; align-items:center">
                            <div>
                                <div style="font-weight:600; font-size:14px">{{ $m->name }}</div>
                                <div style="font-size:12px; color:var(--text-muted)">{{ $m->provider ?? 'No Provider' }}</div>
                            </div>
                            <span class="badge {{ $m->type==='cash' ? 'badge--amber' : 'badge--blue' }}" style="font-size:10px">{{ strtoupper($m->type) }}</span>
                        </div>
                        @empty
                        <div style="padding:20px; text-align:center; color:var(--text-muted)">No active methods.</div>
                        @endforelse
                    </div>
                </template>

                <template x-if="openModal === 'digital'">
                    <div>
                        @forelse($performance->where('type', 'digital') as $m)
                        <div style="padding:12px 20px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; align-items:center">
                            <div>
                                <div style="font-weight:600; font-size:14px">{{ $m->name }}</div>
                                <div style="font-size:12px; color:var(--text-muted)">{{ $m->trx_count }} TRX</div>
                            </div>
                            <div style="font-weight:700; font-size:14px">Rp {{ number_format($m->revenue, 0, ',', '.') }}</div>
                        </div>
                        @empty
                        <div style="padding:20px; text-align:center; color:var(--text-muted)">No digital transactions.</div>
                        @endforelse
                    </div>
                </template>

                <template x-if="openModal === 'cash'">
                    <div>
                        @forelse($performance->where('type', 'cash') as $m)
                        <div style="padding:12px 20px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; align-items:center">
                            <div>
                                <div style="font-weight:600; font-size:14px">{{ $m->name }}</div>
                                <div style="font-size:12px; color:var(--text-muted)">{{ $m->trx_count }} TRX</div>
                            </div>
                            <div style="font-weight:700; font-size:14px">Rp {{ number_format($m->revenue, 0, ',', '.') }}</div>
                        </div>
                        @empty
                        <div style="padding:20px; text-align:center; color:var(--text-muted)">No cash transactions.</div>
                        @endforelse
                    </div>
                </template>

                <template x-if="openModal === 'add'">
                    <div style="padding:20px;">
                        <form method="POST" action="{{ route('payment-methods.store') }}" style="display:flex; flex-direction:column; gap:12px">
                            @csrf
                            <div class="card-grid card-grid--2" style="gap:10px">
                                <div class="form-group">
                                    <label class="form-label">Method Name <span style="color:red">*</span></label>
                                    <input class="form-input" type="text" name="name" value="{{ old('name') }}" placeholder="e.g. ShopeePay" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Method Code <span style="color:red">*</span></label>
                                    <input class="form-input" type="text" name="code" value="{{ old('code') }}" placeholder="e.g. shopeepay" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Type <span style="color:red">*</span></label>
                                    <select class="form-select" name="type" required>
                                        <option value="digital" {{ old('type') == 'digital' ? 'selected' : '' }}>Digital</option>
                                        <option value="cash" {{ old('type') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="edc" {{ old('type') == 'edc' ? 'selected' : '' }}>EDC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Provider</label>
                                    <input class="form-input" type="text" name="provider" value="{{ old('provider') }}" placeholder="Bank or Gateway">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">MDR Fee (%)</label>
                                    <input class="form-input" type="number" name="mdr_fee" value="{{ old('mdr_fee', '0.70') }}" step="0.01">
                                </div>
                            </div>

                            <label style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm)">
                                <span style="font-size:13px; font-weight:500">Initial Status: Active</span>
                                <label class="toggle">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </label>

                            <div class="form-group">
                                <label class="form-label">Internal Notes</label>
                                <textarea class="form-input" name="notes" rows="2" placeholder="Optional notes for administration..." style="resize:none">{{ old('notes') }}</textarea>
                            </div>

                            <div style="display:flex; gap:8px; justify-content:flex-end; border-top:1px solid var(--border-light); padding-top:12px; margin-top:8px">
                                <button type="button" class="btn btn--secondary" @click="openModal = null">Cancel</button>
                                <button type="submit" class="btn btn--primary">Save Channel</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
            
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;

    const trendRaw = @json($trend);
    const allDates = [...new Set(Object.values(trendRaw).flat().map(d => d.date))].sort();
    const methodNames = Object.keys(trendRaw);
    const colors = ['#2563EB','#F59E0B','#EF4444','#10B981','#8B5CF6'];

    const datasets = methodNames.map((method, i) => ({
        label: method,
        data: allDates.map(date => {
            const entry = trendRaw[method]?.find(d => d.date === date);
            return entry ? entry.count : 0;
        }),
        backgroundColor: colors[i % colors.length],
        borderRadius: 4,
    }));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: allDates,
            datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, stacked: false, ticks: { font:{size:11}, color:'#94A3B8' } },
                y: { grid: { color:'#F1F5F9' }, ticks: { font:{size:11}, color:'#94A3B8', stepSize:1 } }
            }
        }
    });
});
</script>
@endpush
