@props(['name', 'id' => null, 'value' => null, 'currency' => 'FCFA', 'invalid' => null])

@php
    $fieldId = $id ?? $name;
    $initial = (int) ($value ?? old($name, 0));
    $hasError = $invalid ?? $errors->has($name);
@endphp

<div
    x-data="{
        raw: {{ $initial }},
        display: '',
        init() { this.display = this.format(this.raw); },
        format(v) { return (v === '' || v === 0) ? '' : new Intl.NumberFormat('fr-GA').format(v).replace(/\u202f/g, ' '); },
        update() {
            this.raw = this.display.replace(/[^\d]/g, '');
            this.display = this.format(this.raw);
        }
    }"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="raw">
    <input
        id="{{ $fieldId }}"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        x-model="display"
        @input="update()"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class([
            'h-[var(--control-h)] w-full rounded-md border bg-inset pl-3 pr-16 text-[16px] text-ink placeholder:text-ink-3 sm:text-sm',
            'text-right tabular-nums',
            $hasError ? 'border-danger' : 'border-control',
            'focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]',
        ]) }}
    >
    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[13px] font-medium text-ink-3">{{ $currency }}</span>
</div>
