@props(['caption' => null, 'flush' => false])

<div {{ $attributes->class(['material-solid overflow-hidden']) }}>
    <div class="scroll-shadow-x">
        <table class="data-table">
            @if ($caption)
                <caption class="sr-only">{{ $caption }}</caption>
            @endif
            {{ $slot }}
        </table>
    </div>
</div>
