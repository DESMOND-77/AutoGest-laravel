@props(['steps' => [], 'current' => 1, 'label' => 'Progression du formulaire'])

<nav aria-label="{{ $label }}" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-2 text-[13px]">
        @foreach ($steps as $index => $step)
            @php
                $number = $index + 1;
                $state = $number < $current ? 'done' : ($number === $current ? 'current' : 'todo');
            @endphp
            <li class="flex items-center gap-2" @if ($state === 'current') aria-current="step" @endif>
                <span class="grid size-7 place-items-center rounded-full text-xs font-semibold
                    {{ $state === 'done' ? 'bg-success-solid text-on-solid' : ($state === 'current' ? 'bg-accent text-on-accent' : 'bg-hover text-ink-3') }}">
                    @if ($state === 'done')
                        <x-ui.icon name="check" class="w-3.5 h-3.5" />
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span class="{{ $state === 'current' ? 'font-semibold text-ink' : 'text-ink-2' }}">{{ $step }}</span>
                @unless ($loop->last)
                    <x-ui.icon name="chevron-right" class="w-3.5 h-3.5 text-ink-3" />
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
