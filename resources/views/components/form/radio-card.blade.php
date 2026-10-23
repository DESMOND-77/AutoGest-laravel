@props(['name', 'value', 'checked' => false, 'title', 'description' => null, 'icon' => null])

@php
    $fieldId = $name.'-'.$value;
@endphp

<label for="{{ $fieldId }}" class="flex cursor-pointer items-start gap-3 rounded-md border bg-solid p-3.5 transition-colors has-[:checked]:border-accent has-[:checked]:bg-accent-soft hover:bg-hover focus-within:shadow-[var(--focus-ring)]">
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="radio"
        value="{{ $value }}"
        @checked($checked || old($name) == $value)
        class="mt-0.5 size-5 shrink-0 border border-control accent-[var(--accent)] focus-visible:outline-none"
    >
    @if ($icon)
        <x-ui.icon :name="$icon" class="mt-0.5 w-5 h-5 text-ink-3" />
    @endif
    <span class="min-w-0">
        <span class="block text-sm font-medium text-ink">{{ $title }}</span>
        @if ($description)
            <span class="mt-0.5 block text-[12.5px] text-ink-2">{{ $description }}</span>
        @endif
        {{ $slot }}
    </span>
</label>
