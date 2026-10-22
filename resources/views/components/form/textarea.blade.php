@props(['name', 'id' => null, 'value' => null, 'rows' => 3, 'invalid' => null])

@php
    $fieldId = $id ?? $name;
    $hasError = $invalid ?? $errors->has($name);
@endphp

<textarea
    id="{{ $fieldId }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
    {{ $attributes->class([
        'w-full rounded-md border bg-inset px-3 py-2.5 text-[16px] leading-relaxed text-ink placeholder:text-ink-3 sm:text-sm',
        $hasError ? 'border-danger' : 'border-control',
        'focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]',
    ]) }}
>{{ $value ?? old($name) }}</textarea>
