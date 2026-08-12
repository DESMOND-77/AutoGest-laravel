@props(['tabs', 'active' => null])

@php $active = $active ?? array_key_first($tabs); @endphp

<div x-data="{ tab: '{{ $active }}' }">
    <div class="flex gap-1 overflow-x-auto border-b border-border/60 mb-5" role="tablist">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'text-primary border-primary' : 'text-content-secondary border-transparent hover:text-content'"
                class="shrink-0 px-3.5 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                role="tab"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{ $slot }}
</div>
