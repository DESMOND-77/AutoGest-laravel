@props(['label' => null, 'vertical' => false])

@if ($vertical)
    <span role="separator" aria-orientation="vertical" {{ $attributes->class(['mx-2 inline-block w-px self-stretch bg-line']) }}></span>
@elseif ($label)
    <div role="separator" {{ $attributes->class(['flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-ink-3']) }}>
        <span class="h-px flex-1 bg-line"></span>{{ $label }}<span class="h-px flex-1 bg-line"></span>
    </div>
@else
    <hr role="separator" {{ $attributes->class(['border-0 border-t border-line']) }}>
@endif
