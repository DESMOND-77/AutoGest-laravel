@props(['names' => [], 'max' => 3, 'size' => 'sm'])

<div {{ $attributes->class(['flex items-center -space-x-2']) }}>
    @foreach (collect($names)->take($max) as $name)
        <x-ui.avatar :name="$name" :size="$size" class="ring-2 ring-solid" />
    @endforeach
    @if (collect($names)->count() > $max)
        <span class="grid size-8 place-items-center rounded-full bg-hover text-xs font-semibold text-ink-2 ring-2 ring-solid">
            +{{ collect($names)->count() - $max }}
        </span>
    @endif
</div>
