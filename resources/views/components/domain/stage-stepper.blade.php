@props(['stage', 'variant' => 'full'])

@php
    $enum = \App\Domain\Students\Enums\LifecycleStage::class;
    $current = $stage instanceof $enum ? $stage : $enum::tryFrom((string) $stage);
    $phases = config('ui-status.phases', []);
    $allStages = collect($enum::cases())->values();
    $currentKey = $current?->value;
    $currentStep = $currentKey ? $allStages->search(fn ($s) => $s->value === $currentKey) + 1 : 0;
    $currentPhaseIndex = 0;
    foreach ($phases as $index => $phase) {
        if (in_array($currentKey, $phase['stages'], true)) {
            $currentPhaseIndex = $index;
        }
    }
@endphp

@if ($variant === 'ring')
    <x-ui.progress variant="ring" :value="$currentStep" :max="15" :label="'Étape '.$currentStep.' sur 15'" />
@elseif ($variant === 'compact')
    <p class="flex flex-wrap items-center gap-2 text-[13px] text-ink-2">
        <x-ui.status :value="$current" />
        <span class="text-ink-3">Étape {{ $currentStep }} sur 15</span>
    </p>
@else
    <div {{ $attributes }}>
        <ol class="grid grid-cols-5 gap-1.5" aria-label="Parcours de l'élève en 5 phases">
            @foreach ($phases as $index => $phase)
                @php
                    $state = $index < $currentPhaseIndex ? 'done' : ($index === $currentPhaseIndex ? 'current' : 'todo');
                @endphp
                <li class="min-w-0" @if ($state === 'current') aria-current="step" @endif>
                    <div class="flex items-center gap-1.5">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full text-[11px] font-bold
                            {{ $state === 'done' ? 'bg-success-solid text-on-solid' : ($state === 'current' ? 'bg-accent text-on-accent' : 'bg-hover text-ink-3') }}">
                            @if ($state === 'done')
                                <x-ui.icon name="check" class="w-3 h-3" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span class="truncate text-[12px] font-semibold {{ $state === 'todo' ? 'text-ink-3' : 'text-ink' }}">{{ $phase['label'] }}</span>
                    </div>
                    <div class="mt-2 h-1.5 rounded-pill {{ $state === 'todo' ? 'bg-hover' : ($state === 'current' ? 'bg-accent' : 'bg-success-solid') }}"></div>
                </li>
            @endforeach
        </ol>

        <p class="mt-3 flex flex-wrap items-center gap-2 text-[13px] text-ink-2">
            <span class="font-medium text-ink">Étape actuelle :</span>
            <x-ui.status :value="$current" />
            <span class="text-ink-3">Étape {{ $currentStep }} sur 15</span>
        </p>
    </div>
@endif
