@props(['name', 'id' => null, 'options' => [], 'selected' => null, 'placeholder' => null, 'invalid' => null])

@php
    $fieldId = $id ?? $name;
    $current = (string) ($selected ?? old($name, ''));
    $hasError = $invalid ?? $errors->has($name);
@endphp

<div class="relative">
    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class([
            'h-[var(--control-h)] w-full appearance-none rounded-md border bg-inset pl-3 pr-9 text-[16px] text-ink sm:text-sm',
            $hasError ? 'border-danger' : 'border-control',
            'focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]',
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-3">
        <x-ui.icon name="chevron-down" class="w-4 h-4" />
    </span>
</div>
