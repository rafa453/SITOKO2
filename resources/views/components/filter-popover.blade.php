@props([
    'title' => 'Filter',
    'formId' => 'filterForm',
    'isActive' => false,
])

<div style="position: relative;" x-data="{ showFilters: false }">
    <button type="button"
            class="btn-icon"
            title="{{ $title }}"
            @click="showFilters = !showFilters"
            :style="showFilters ? 'background:var(--border-light); color:var(--blue-600)' : '{{ $isActive ? 'color:var(--blue-600)' : '' }}'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>
        @if($isActive)
            <span style="position:absolute; top:2px; right:2px; width:6px; height:6px; background:var(--blue-600); border-radius:50%"></span>
        @endif
    </button>

    <div x-show="showFilters"
         @click.away="showFilters = false"
         x-cloak
         style="display:none; position:absolute; top:100%; right:0; z-index:100; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); box-shadow:0 10px 25px rgba(15,23,42,0.15); padding:16px; width:260px; margin-top:8px;">
        <div style="font-size:13px; font-weight:700; margin-bottom:12px; color:var(--text-primary); border-bottom:1px solid var(--border-light); padding-bottom:8px;">
            {{ $title }}
        </div>

        <div style="display:flex; flex-direction:column; gap:10px">
            {{ $slot }}

            <div style="display:flex; gap:8px; margin-top:8px">
                <button type="submit" form="{{ $formId }}" class="btn btn--primary btn--sm" style="flex:1; justify-content:center">Terapkan</button>
                @if($isActive)
                    <a href="{{ url()->current() }}" class="btn btn--secondary btn--sm" style="flex:1; justify-content:center">Reset</a>
                @endif
            </div>
        </div>
    </div>
</div>
