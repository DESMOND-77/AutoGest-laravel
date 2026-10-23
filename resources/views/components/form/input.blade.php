@props([
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'icon' => null,
    'suffix' => null,
    'invalid' => null,
])

@php
    $fieldId = $id ?? $name;
    $current = $value ?? old($name);
    $hasError = $invalid ?? $errors->has($name);
@endphp

<div class="relative">
    @if ($icon)
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-3">
            <x-ui.icon :name="$icon" class="w-4 h-4" />
        </span>
    @endif

    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : $current }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class([
            'w-full rounded-md border bg-inset text-[16px] text-ink placeholder:text-ink-3 sm:text-sm',
            'h-[var(--control-h)] px-3',
            $icon ? 'pl-9' : null,
            $suffix ? 'pr-14' : null,
            $hasError ? 'border-danger' : 'border-control',
            'focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]',
        ]) }}
    >

    @if ($suffix)
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[13px] font-medium text-ink-3">{{ $suffix }}</span>
    @endif
</div>
