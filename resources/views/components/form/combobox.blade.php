@props(['name', 'options' => [], 'selected' => null, 'placeholder' => 'Rechercher…', 'id' => null])

@php
    $fieldId = $id ?? $name;
    $items = collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all();
    $current = (string) ($selected ?? old($name, ''));
    $currentLabel = collect($items)->firstWhere('value', $current)['label'] ?? '';
    $hasError = $errors->has($name);
@endphp

<div
    x-data="{
        open: false,
        query: @js($currentLabel),
        value: @js($current),
        items: @js($items),
        get filtered() {
            if (!this.query) return this.items.slice(0, 30);
            const q = this.query.toLowerCase();
            return this.items.filter((i) => i.label.toLowerCase().includes(q)).slice(0, 30);
        },
        choose(item) { this.value = item.value; this.query = item.label; this.open = false; },
        clear() { this.value = ''; this.query = ''; }
    }"
    @click.outside="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="value">
    <input
        id="{{ $fieldId }}"
        type="text"
        x-model="query"
        @focus="open = true"
        @input="open = true"
        @keydown.escape="open = false"
        @keydown.arrow-down.prevent="open = true"
        autocomplete="off"
        role="combobox"
        aria-expanded="false"
        :aria-expanded="open.toString()"
        aria-controls="{{ $fieldId }}-list"
        placeholder="{{ $placeholder }}"
        @if ($hasError) aria-invalid="true" @endif
        class="h-[var(--control-h)] w-full rounded-md border bg-inset px-3 text-[16px] text-ink placeholder:text-ink-3 sm:text-sm focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] {{ $hasError ? 'border-danger' : 'border-control' }}"
    >
    <ul
        id="{{ $fieldId }}-list"
        x-show="open"
        x-cloak
        role="listbox"
        class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-line bg-solid p-1 shadow-lg"
    >
        <template x-for="item in filtered" :key="item.value">
            <li role="option" :aria-selected="(item.value === value).toString()">
                <button type="button" @click="choose(item)"
                    class="w-full rounded-sm px-2.5 py-2 text-left text-sm text-ink hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                    x-text="item.label"></button>
            </li>
        </template>
        <li x-show="filtered.length === 0" class="px-2.5 py-2 text-sm text-ink-3">Aucun résultat.</li>
    </ul>
</div>
