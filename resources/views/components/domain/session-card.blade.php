@props(['session', 'variant' => 'row', 'conflict' => false, 'showStudent' => true])

@php
    $time = \Illuminate\Support\Carbon::parse($session->starts_at)->format('H:i').' – '.\Illuminate\Support\Carbon::parse($session->ends_at)->format('H:i');
@endphp

@if ($variant === 'block')
    <div {{ $attributes->class(['session-block rounded-sm bg-accent-soft p-1.5 text-[11px] leading-tight text-ink']) }} data-conflict="{{ $conflict ? '1' : '0' }}">
        <p class="font-semibold">{{ $time }}</p>
        <p class="truncate">{{ $session->type?->label() }}</p>
        @if ($showStudent && $session->student)
            <p class="truncate text-ink-2">{{ $session->student->fullName() }}</p>
        @endif
        @if ($conflict)
            <p class="mt-0.5 flex items-center gap-1 font-semibold text-danger">
                <x-ui.icon name="circle-alert" class="w-3 h-3" /> Conflit
            </p>
        @endif
    </div>
@else
    <article {{ $attributes->class(['material-solid flex flex-col gap-2 p-3.5']) }} data-conflict="{{ $conflict ? '1' : '0' }}">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[13px] font-semibold tabular-nums text-ink">{{ $time }}</p>
                <p class="truncate text-sm font-medium text-ink">{{ $session->type?->label() }}</p>
            </div>
            <x-ui.status :value="$session->presence" />
        </div>
        @if ($showStudent && $session->student)
            <p class="flex items-center gap-1.5 text-[13px] text-ink-2">
                <x-ui.icon name="user" class="w-3.5 h-3.5" /> {{ $session->student->fullName() }}
            </p>
        @endif
        @if ($session->vehicle ?? null)
            <p class="flex items-center gap-1.5 text-[13px] text-ink-2">
                <x-ui.icon name="car" class="w-3.5 h-3.5" /> {{ $session->vehicle->plate }}
            </p>
        @endif
        @if ($session->location)
            <p class="flex items-center gap-1.5 text-[13px] text-ink-2">
                <x-ui.icon name="map-pin" class="w-3.5 h-3.5" /> {{ $session->location }}
            </p>
        @endif
        @if ($conflict)
            <p class="flex items-center gap-1.5 text-[12px] font-semibold text-danger">
                <x-ui.icon name="circle-alert" class="w-3.5 h-3.5" /> Conflit moniteur ou véhicule
            </p>
        @endif
        {{ $slot }}
    </article>
@endif
