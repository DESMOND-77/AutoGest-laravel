<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-pill whitespace-nowrap font-medium', $compact ? 'px-2 py-0.5 text-[11.5px]' : 'px-2.5 py-0.5 text-[12px]', $toneClasses()]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dotClass() }}" aria-hidden="true"></span>
    @elseif ($icon)
        <x-ui.icon :name="$icon" class="w-3.5 h-3.5" />
    @endif
    {{ $label }}
</span>
