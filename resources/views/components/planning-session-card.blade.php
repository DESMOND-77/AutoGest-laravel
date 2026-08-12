@props(['session', 'dense' => false, 'showInstructor' => true])

@php
    $typeStyles = [
        'theoretical' => 'bg-purple-100 border-purple-400 text-purple-900 dark:bg-purple-900/50 dark:border-purple-500 dark:text-purple-100',
        'practical' => 'bg-blue-100 border-blue-400 text-blue-900 dark:bg-blue-900/50 dark:border-blue-500 dark:text-blue-100',
        'code' => 'bg-amber-100 border-amber-400 text-amber-900 dark:bg-amber-900/50 dark:border-amber-500 dark:text-amber-100',
        'mock_exam' => 'bg-red-100 border-red-400 text-red-900 dark:bg-red-900/50 dark:border-red-500 dark:text-red-100',
    ];

    $isCancelled = $session->presence === \App\Domain\Scheduling\Enums\PresenceStatus::Cancelled;
    $colorClasses = $isCancelled
        ? 'bg-gray-100 border-gray-300 text-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500'
        : ($typeStyles[$session->type->value] ?? $typeStyles['practical']);

    $canMarkPresence = auth()->user()->can('markPresence', $session);
    $canCancel = auth()->user()->can('update', $session) && ! $isCancelled;
    $canLinkToEvaluation = auth()->user()->hasAnyRole(['admin', 'moniteur']) && auth()->user()->can('view', $session->student);
@endphp

<div class="{{ $colorClasses }} border-l-4 rounded-md {{ $dense ? 'px-1 py-0.5' : 'px-3 py-2' }} text-xs leading-tight {{ $isCancelled ? 'line-through' : '' }}">
    <div class="font-semibold {{ $dense ? '' : 'text-sm' }}">
        {{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}
        @unless ($dense)
            <span class="font-normal">· {{ $session->type->label() }}</span>
        @endunless
    </div>

    <div class="truncate">
        @if ($canLinkToEvaluation)
            <a href="{{ route('training.evaluation.show', $session->student) }}" class="hover:underline">{{ $session->student->fullName() }}</a>
        @else
            {{ $session->student->fullName() }}
        @endif
    </div>

    @unless ($dense)
        @if ($showInstructor)
            <div class="truncate opacity-80">{{ $session->instructor->name }}</div>
        @endif

        @if ($session->vehicle)
            <div class="truncate opacity-70">{{ $session->vehicle->plate }}</div>
        @endif
    @endunless

    @if ($dense)
        {{-- Compact controls: an icon-sized presence select and a small
             cancel button, so even a 1-hour slot has room for both without
             the card content overflowing its time-proportional height. --}}
        <div class="mt-0.5 flex items-center gap-1">
            @if ($canMarkPresence)
                <form method="POST" action="{{ route('scheduling.presence', $session) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <select
                        name="presence"
                        class="text-[10px] leading-none py-0 pl-0.5 pr-3 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded"
                        onchange="this.form.submit()"
                        aria-label="Changer la présence"
                    >
                        @foreach (\App\Domain\Scheduling\Enums\PresenceStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($session->presence === $case)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </form>
            @else
                <span class="text-[10px] opacity-80 truncate">{{ $session->presence->label() }}</span>
            @endif

            @if ($canCancel)
                <form method="POST" action="{{ route('scheduling.destroy', $session) }}" class="inline" onsubmit="return confirm('Annuler cette séance ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-700 dark:text-red-300 leading-none" title="Annuler la séance" aria-label="Annuler la séance">&times;</button>
                </form>
            @endif
        </div>
    @else
        <div class="mt-1 flex items-center gap-2 flex-wrap">
            <span class="opacity-80">{{ $session->presence->label() }}</span>

            @if ($canMarkPresence)
                <form method="POST" action="{{ route('scheduling.presence', $session) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <select
                        name="presence"
                        class="text-[11px] py-0 pl-1 pr-5 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded"
                        onchange="this.form.submit()"
                        aria-label="Changer la présence"
                    >
                        @foreach (\App\Domain\Scheduling\Enums\PresenceStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($session->presence === $case)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if ($canCancel)
                <form method="POST" action="{{ route('scheduling.destroy', $session) }}" class="inline" onsubmit="return confirm('Annuler cette séance ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-700 dark:text-red-300 underline">Annuler</button>
                </form>
            @endif
        </div>
    @endif
</div>
