@props(['name', 'id' => null, 'value' => '1', 'checked' => false, 'label' => null, 'description' => null])

@php
    $fieldId = $id ?? $name;
@endphp

<label for="{{ $fieldId }}" class="flex cursor-pointer items-start gap-3 py-1.5">
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="checkbox"
        value="{{ $value }}"
        @checked($checked ?? old($name))
        {{ $attributes->class(['mt-0.5 size-5 shrink-0 rounded-xs border border-control text-accent accent-[var(--accent)] focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]']) }}
    >
    @if ($label || $description)
        <span class="min-w-0">
            @if ($label)<span class="block text-sm text-ink">{{ $label }}</span>@endif
            @if ($description)<span class="block text-[12.5px] text-ink-3">{{ $description }}</span>@endif
        </span>
    @else
        {{ $slot }}
    @endif
</label>
