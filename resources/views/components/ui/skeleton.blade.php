@props(['type' => 'line', 'lines' => 3])

<div {{ $attributes->class(['animate-pulse']) }} aria-hidden="true">
    @if ($type === 'line')
        <div class="space-y-2">
            @for ($i = 0; $i < $lines; $i++)
                <div class="h-3 rounded-xs bg-hover" style="width: {{ $i === $lines - 1 ? '60' : '100' }}%"></div>
            @endfor
        </div>
    @elseif ($type === 'kpi')
        <div class="space-y-3">
            <div class="h-3 w-24 rounded-xs bg-hover"></div>
            <div class="h-7 w-32 rounded-xs bg-hover"></div>
            <div class="h-8 w-full rounded-sm bg-hover"></div>
        </div>
    @elseif ($type === 'row')
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-full bg-hover"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 w-1/3 rounded-xs bg-hover"></div>
                <div class="h-3 w-1/2 rounded-xs bg-hover"></div>
            </div>
        </div>
    @else
        <div class="h-full min-h-24 rounded-md bg-hover"></div>
    @endif
</div>
