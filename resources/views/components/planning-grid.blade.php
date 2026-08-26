@props(['sessions', 'week', 'daysCount' => 6, 'showInstructor' => true])

@php
    $dayDates = collect(range(0, $daysCount - 1))->map(fn ($offset) => $week->copy()->addDays($offset));

    $sessionsByDay = $sessions->groupBy(fn ($session) => $session->scheduled_date->toDateString());

    $times = $sessions->flatMap(fn ($session) => [
        \Illuminate\Support\Carbon::parse($session->starts_at),
        \Illuminate\Support\Carbon::parse($session->ends_at),
    ]);

    // Default business-hours window, widened automatically if a session
    // falls outside it - the legacy grid's hard-coded 07:00-17:00 rows
    // silently dropped anything outside that range (see the audit's
    // legacy-feature-parity notes); this one never hides a session.
    $dayStartHour = min(7, $times->min(fn ($t) => $t->hour) ?? 7);
    $dayEndHour = max(19, $times->max(fn ($t) => $t->minute > 0 ? $t->hour + 1 : $t->hour) ?? 19);
    $totalMinutes = ($dayEndHour - $dayStartHour) * 60;
    $pixelsPerHour = 56;
    $gridHeight = ($dayEndHour - $dayStartHour) * $pixelsPerHour;

    $hourMarks = range($dayStartHour, $dayEndHour - 1);
@endphp

<div {{ $attributes->class(['bg-surface rounded-ui-lg shadow-soft p-4']) }}>
    {{-- Legend --}}
    <div class="flex flex-wrap gap-3 text-xs text-content-secondary mb-4">
        @foreach (\App\Domain\Scheduling\Enums\SessionType::cases() as $case)
            @php
                $dotClasses = [
                    'theoretical' => 'bg-info',
                    'practical' => 'bg-primary',
                    'code' => 'bg-warning',
                    'mock_exam' => 'bg-danger',
                ][$case->value];
            @endphp
            <span class="inline-flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full {{ $dotClasses }}"></span>
                {{ $case->label() }}
            </span>
        @endforeach
        <span class="inline-flex items-center gap-1">
            <span class="w-2.5 h-2.5 rounded-full bg-content-muted"></span>
            Annulée
        </span>
    </div>

    {{-- Desktop grid --}}
    <div class="hidden md:block overflow-x-auto">
        <div class="min-w-[720px]">
            <div class="grid" style="grid-template-columns: 56px repeat({{ $daysCount }}, minmax(0, 1fr));">
                <div></div>
                @foreach ($dayDates as $date)
                    <div class="text-center text-xs font-medium text-content-secondary pb-2 capitalize">
                        {{ $date->translatedFormat('D d/m') }}
                    </div>
                @endforeach
            </div>

            <div class="grid" style="grid-template-columns: 56px repeat({{ $daysCount }}, minmax(0, 1fr));">
                <div class="relative" style="height: {{ $gridHeight }}px;">
                    @foreach ($hourMarks as $hour)
                        <div
                            class="absolute right-2 -translate-y-1/2 text-[11px] text-content-muted"
                            style="top: {{ ($hour - $dayStartHour) * $pixelsPerHour }}px;"
                        >
                            {{ sprintf('%02d:00', $hour) }}
                        </div>
                    @endforeach
                </div>

                @foreach ($dayDates as $date)
                    <div
                        class="relative border-l border-border/60"
                        style="height: {{ $gridHeight }}px;"
                        data-day-column
                        data-date="{{ $date->toDateString() }}"
                        data-day-start-hour="{{ $dayStartHour }}"
                        data-day-end-hour="{{ $dayEndHour }}"
                    >
                        @foreach ($hourMarks as $hour)
                            <div
                                class="absolute left-0 right-0 border-t border-border/40"
                                style="top: {{ ($hour - $dayStartHour) * $pixelsPerHour }}px;"
                            ></div>
                        @endforeach

                        @foreach ($sessionsByDay->get($date->toDateString(), collect()) as $session)
                            @php
                                $start = \Illuminate\Support\Carbon::parse($session->starts_at);
                                $end = \Illuminate\Support\Carbon::parse($session->ends_at);
                                $startMinutes = ($start->hour - $dayStartHour) * 60 + $start->minute;
                                $durationMinutes = max(15, $start->diffInMinutes($end));
                                $topPx = $startMinutes / 60 * $pixelsPerHour;
                                // min-height (not height) so the card is never
                                // shorter than its content needs - a 30-45min
                                // session still has room for the presence/
                                // cancel controls instead of clipping them.
                                $minHeightPx = max(44, $durationMinutes / 60 * $pixelsPerHour);
                            @endphp
                            <div
                                class="absolute left-0.5 right-0.5 z-10"
                                style="top: {{ $topPx }}px; min-height: {{ $minHeightPx }}px;"
                            >
                                <x-planning-session-card :session="$session" :dense="true" :show-instructor="$showInstructor" />
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Mobile fallback: stacked cards per day instead of a horizontally-scrolling grid --}}
    <div class="md:hidden space-y-4">
        @foreach ($dayDates as $date)
            @php
                $daySessions = $sessionsByDay->get($date->toDateString(), collect())
                    ->sortBy('starts_at');
            @endphp
            <div>
                <div class="text-xs font-medium text-content-secondary mb-2 capitalize">
                    {{ $date->translatedFormat('l d/m') }}
                </div>
                @if ($daySessions->isEmpty())
                    <p class="text-xs text-content-muted">Aucune séance.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($daySessions as $session)
                            <x-planning-session-card :session="$session" :show-instructor="$showInstructor" />
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
