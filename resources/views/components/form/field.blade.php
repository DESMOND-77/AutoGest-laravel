@props([
    'name',
    'label',
    'id' => null,
    'hint' => null,
    'required' => false,
    'error' => null,
    'optional' => false,
])

@php
    $fieldId = $id ?? $name;
    $message = $error ?? ($errors->has($name) ? $errors->first($name) : null);
    $describedBy = collect([$hint ? $fieldId.'-hint' : null, $message ? $fieldId.'-error' : null])->filter()->implode(' ');
@endphp

<div {{ $attributes->class(['space-y-1.5']) }}>
    <label for="{{ $fieldId }}" class="flex flex-wrap items-baseline gap-2 text-sm font-medium text-ink">
        <span>{{ $label }}</span>
        @if ($required)
            <span class="text-[12px] font-normal text-ink-3">(obligatoire)</span>
        @elseif ($optional)
            <span class="text-[12px] font-normal text-ink-3">(facultatif)</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint && ! $message)
        <p id="{{ $fieldId }}-hint" class="text-[12.5px] text-ink-3">{{ $hint }}</p>
    @endif
    @if ($message)
        <p id="{{ $fieldId }}-error" class="flex items-start gap-1.5 text-[12.5px] font-medium text-danger">
            <x-ui.icon name="circle-alert" class="mt-0.5 w-3.5 h-3.5" />
            <span>{{ $message }}</span>
        </p>
    @endif
</div>
