@props(['name' => '', 'src' => null, 'size' => 'md', 'status' => null, 'label' => null])

@php
    $sizes = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-base', 'xl' => 'size-20 text-xl'];
    $initials = collect(explode(' ', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $tones = ['bg-accent-soft text-accent', 'bg-info-soft text-info', 'bg-success-soft text-success', 'bg-warning-soft text-warning', 'bg-danger-soft text-danger'];
    $tone = $tones[crc32((string) $name) % count($tones)];
@endphp

<span {{ $attributes->class(['relative inline-flex shrink-0 items-center justify-center rounded-full', $sizes[$size] ?? $sizes['md']]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $label ?? $name }}" class="size-full rounded-full object-cover" width="80" height="80" loading="lazy">
    @else
        <span class="flex size-full items-center justify-center rounded-full font-semibold {{ $tone }}" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>{{ $initials ?: '?' }}</span>
    @endif
    @if ($status)
        <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-solid bg-success-solid" aria-hidden="true"></span>
    @endif
</span>
