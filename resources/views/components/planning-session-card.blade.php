@props(['session', 'dense' => false, 'showInstructor' => true])

@php
    $typeStyles = [
        'theoretical' => 'bg-info/15 border-info text-content',
        'practical' => 'bg-primary/15 border-primary text-content',
        'code' => 'bg-info/15 border-info text-content',
        'mock_exam' => 'bg-warning/15 border-warning text-content',
    ];

    $isCancelled = $session->presence === \App\Domain\Scheduling\Enums\PresenceStatus::Cancelled;
    $colorClasses = $isCancelled
        ? 'bg-surface-inset border-border text-content-muted'
        : ($typeStyles[$session->type->value] ?? $typeStyles['practical']);

    $canMarkPresence = auth()->user()->can('markPresence', $session);
    $canCancel = auth()->user()->can('update', $session) && ! $isCancelled;
    $canEdit = auth()->user()->can('update', $session);
    $canLinkToEvaluation = auth()->user()->hasAnyRole(['admin', 'moniteur']) && auth()->user()->can('view', $session->student);

    $sessionPayload = [
        'id' => $session->id,
        'student_name' => $session->student->fullName(),
        'instructor_id' => $session->instructor_id,
        'vehicle_id' => $session->vehicle_id,
        'type' => $session->type->value,
        'scheduled_date' => $session->scheduled_date->toDateString(),
        'starts_at' => substr($session->starts_at, 0, 5),
        'ends_at' => substr($session->ends_at, 0, 5),
    ];
@endphp

<div
    data-session-card
    @if ($canEdit) @click="editingSession = {{ Illuminate\Support\Js::from($sessionPayload) }}; showEditModal = true" @endif
    class="{{ $colorClasses }} border-l-4 rounded-ui-sm {{ $dense ? 'px-1.5 py-1' : 'px-3 py-2' }} text-xs leading-tight {{ $isCancelled ? 'line-through' : '' }} {{ $canEdit ? 'cursor-pointer hover:shadow-soft-sm transition' : '' }}"
>
    <div class="font-semibold {{ $dense ? '' : 'text-sm' }}">
        {{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}
        @unless ($dense)
            <span class="font-normal">· {{ $session->type->label() }}</span>
        @endunless
    </div>

    <div class="truncate">
        @if ($canLinkToEvaluation)
            <a href="{{ route('training.evaluation.show', $session->student) }}" @click.stop class="hover:underline">{{ $session->student->fullName() }}</a>
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
        <div class="mt-0.5 flex items-center gap-1" @click.stop>
            @if ($canMarkPresence)
                <form method="POST" action="{{ route('scheduling.presence', $session) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <select
                        name="presence"
                        class="text-[10px] leading-none py-0 pl-0.5 pr-3 border-0 bg-transparent text-content rounded"
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
                    <button type="submit" class="text-danger leading-none" title="Annuler la séance" aria-label="Annuler la séance">&times;</button>
                </form>
            @endif
        </div>
    @else
        <div class="mt-1 flex items-center gap-2 flex-wrap" @click.stop>
            <span class="opacity-80">{{ $session->presence->label() }}</span>

            @if ($canMarkPresence)
                <form method="POST" action="{{ route('scheduling.presence', $session) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <select
                        name="presence"
                        class="text-[11px] py-0 pl-1 pr-5 border-0 bg-transparent text-content rounded"
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
                    <button type="submit" class="text-danger underline">Annuler</button>
                </form>
            @endif
        </div>
    @endif
</div>
