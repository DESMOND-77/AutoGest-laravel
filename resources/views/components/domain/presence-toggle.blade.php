@props(['session', 'size' => 'md'])

@php
    $states = [
        ['value' => 'present', 'label' => 'Présent', 'icon' => 'circle-check'],
        ['value' => 'absent', 'label' => 'Absent', 'icon' => 'circle-x'],
    ];
    $current = $session->presence?->value;
@endphp

<form method="POST" action="{{ route('scheduling.presence', $session) }}" class="inline-flex" x-data>
    @csrf
    @method('PATCH')
    <fieldset>
        <legend class="sr-only">Présence de la séance</legend>
        <div class="inline-flex rounded-pill bg-hover p-0.5">
            @foreach ($states as $state)
                <button
                    type="submit"
                    name="presence"
                    value="{{ $state['value'] }}"
                    aria-pressed="{{ $current === $state['value'] ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-1.5 rounded-pill px-3 font-medium transition-colors focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] {{ $size === 'lg' ? 'h-11 text-sm' : 'h-8 text-[12.5px]' }}
                        {{ $current === $state['value']
                            ? ($state['value'] === 'present' ? 'bg-success-solid text-on-solid' : 'bg-danger-solid text-on-solid')
                            : 'text-ink-2 hover:text-ink' }}"
                >
                    <x-ui.icon :name="$state['icon']" class="w-4 h-4" />
                    {{ $state['label'] }}
                </button>
            @endforeach
        </div>
    </fieldset>
</form>
