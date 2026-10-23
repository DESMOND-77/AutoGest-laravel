@php
    $days = collect(range(0, 6))->map(fn ($i) => $week->copy()->startOfWeek()->addDays($i));
    $conflictIds = [];
    $list = $sessions->values();
    foreach ($list as $i => $a) {
        foreach ($list as $j => $b) {
            if ($j <= $i) {
                continue;
            }
            $sameDate = $a->scheduled_date?->toDateString() === $b->scheduled_date?->toDateString();
            $overlap = $a->starts_at < $b->ends_at && $b->starts_at < $a->ends_at;
            $sharedResource = $a->instructor_id === $b->instructor_id
                || ($a->vehicle_id !== null && $a->vehicle_id === $b->vehicle_id);
            if ($sameDate && $overlap && $sharedResource) {
                $conflictIds[$a->id] = true;
                $conflictIds[$b->id] = true;
            }
        }
    }

    $payload = $list->map(fn ($session) => [
        'id' => $session->id,
        'date' => $session->scheduled_date?->toDateString(),
        'start' => substr((string) $session->starts_at, 0, 5),
        'end' => substr((string) $session->ends_at, 0, 5),
        'student' => $session->student?->fullName() ?? '—',
        'student_id' => $session->student_id,
        'instructor' => $session->instructor?->name ?? '—',
        'instructor_id' => $session->instructor_id,
        'vehicle' => $session->vehicle?->plate,
        'vehicle_id' => $session->vehicle_id,
        'type' => $session->type?->label(),
        'type_value' => $session->type?->value,
        'presence' => $session->presence?->label(),
        'presence_value' => $session->presence?->value,
        'location' => $session->location,
        'conflict' => isset($conflictIds[$session->id]),
    ])->values()->all();

    $defaultDay = $days->first(fn ($day) => $day->isToday())?->toDateString() ?? $days->first()->toDateString();
    $editSession = session('editingSessionId') ? $list->firstWhere('id', session('editingSessionId')) : null;
    $createOpen = $errors->any() && ! $editSession && ! session('duplicatingSessionId');
@endphp

<x-app-layout :title="'Planning'">
    <x-layout.page-header title="Planning" :description="'Semaine du '.$week->copy()->startOfWeek()->format('d/m').' au '.$week->copy()->endOfWeek()->format('d/m/Y')">
        <x-slot:actions>
            <x-ui.button :href="route('scheduling.export.csv', array_merge($filters ?? [], ['week' => $week->toDateString()]))" variant="secondary" icon="download">Exporter CSV</x-ui.button>
            @can('create', \App\Domain\Scheduling\Models\LessonSession::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-session' })">Nouvelle séance</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    @if ($sessions->isEmpty())
        <p class="mb-3 text-[13px] text-ink-3">Aucune séance planifiée cette semaine.</p>
    @endif

    <div
        x-data="{
            view: 'day',
            resource: 'instructors',
            day: @js($defaultDay),
            selected: null,
            sessions: @js($payload),
            urls: {
                update: @js(route('scheduling.update', ['session' => '__ID__'])),
                duplicate: @js(route('scheduling.duplicate', ['session' => '__ID__'])),
                destroy: @js(route('scheduling.destroy', ['session' => '__ID__'])),
                presence: @js(route('scheduling.presence', ['session' => '__ID__']))
            },
            instructors: @js($instructors->map(fn ($i) => ['id' => $i->id, 'label' => $i->name])->values()->all()),
            vehicles: @js($vehicles->map(fn ($v) => ['id' => $v->id, 'label' => $v->plate])->values()->all()),
            slots: (() => {
                const out = [];
                for (let h = 7; h < 19; h++) {
                    out.push(String(h).padStart(2, '0') + ':00');
                    out.push(String(h).padStart(2, '0') + ':30');
                }
                return out;
            })(),
            get resources() { return this.resource === 'instructors' ? this.instructors : this.vehicles; },
            get daySessions() { return this.sessions.filter((s) => s.date === this.day); },
            urlFor(kind) { return this.urls[kind].replace('__ID__', this.selected ? this.selected.id : ''); },
            forCell(resourceId, slot) {
                return this.daySessions.filter((s) => {
                    const owner = this.resource === 'instructors' ? s.instructor_id : s.vehicle_id;
                    return owner === resourceId && s.start === slot;
                });
            },
            openSession(session) { this.selected = session; },
            closeSession() { this.selected = null; }
        }"
        class="space-y-4"
    >
        {{-- Barre d'outils --}}
        <div class="material-solid flex flex-wrap items-center gap-2 p-3">
            <div class="flex items-center gap-1">
                <x-ui.button :href="route('scheduling.index', array_merge($filters ?? [], ['week' => $week->copy()->subWeek()->toDateString()]))" variant="ghost" size="sm" icon="chevron-left" label="Semaine précédente" />
                <x-ui.button :href="route('scheduling.index', array_merge($filters ?? [], ['week' => $week->copy()->toDateString()]))" variant="secondary" size="sm">Cette semaine</x-ui.button>
                <x-ui.button :href="route('scheduling.index', array_merge($filters ?? [], ['week' => $week->copy()->addWeek()->toDateString()]))" variant="ghost" size="sm" icon-right="chevron-right" label="Semaine suivante" />
            </div>

            <div class="inline-flex rounded-pill bg-hover p-0.5" role="group" aria-label="Période affichée">
                <button type="button" @click="view = 'day'" :aria-pressed="(view === 'day').toString()"
                    :class="view === 'day' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'"
                    class="rounded-pill px-3 py-1 text-[12.5px] font-medium focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">Jour</button>
                <button type="button" @click="view = 'week'" :aria-pressed="(view === 'week').toString()"
                    :class="view === 'week' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'"
                    class="rounded-pill px-3 py-1 text-[12.5px] font-medium focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">Semaine</button>
            </div>

            <div class="inline-flex rounded-pill bg-hover p-0.5" role="group" aria-label="Ressource en colonnes">
                <button type="button" @click="resource = 'instructors'" :aria-pressed="(resource === 'instructors').toString()"
                    :class="resource === 'instructors' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'"
                    class="rounded-pill px-3 py-1 text-[12.5px] font-medium focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">Moniteurs</button>
                <button type="button" @click="resource = 'vehicles'" :aria-pressed="(resource === 'vehicles').toString()"
                    :class="resource === 'vehicles' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'"
                    class="rounded-pill px-3 py-1 text-[12.5px] font-medium focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">Véhicules</button>
            </div>

            <form method="GET" action="{{ route('scheduling.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="week" value="{{ $week->toDateString() }}">
                <x-form.select name="instructor_id" :selected="$filters['instructor_id'] ?? null" placeholder="Tous les moniteurs"
                    :options="$instructors->pluck('name', 'id')->all()" aria-label="Filtrer par moniteur" class="min-w-40" />
                <x-form.select name="vehicle_id" :selected="$filters['vehicle_id'] ?? null" placeholder="Tous les véhicules"
                    :options="$vehicles->pluck('plate', 'id')->all()" aria-label="Filtrer par véhicule" class="min-w-36" />
                <x-ui.button type="submit" size="sm" variant="secondary" icon="filter">Filtrer</x-ui.button>
                @if (array_filter($filters ?? []))
                    <a href="{{ route('scheduling.index', ['week' => $week->toDateString()]) }}" class="text-[12.5px] font-medium text-accent hover:underline">Réinitialiser</a>
                @endif
            </form>

            <p class="ml-auto flex items-center gap-3 text-[11.5px] text-ink-3">
                <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full bg-accent"></span> Conduite</span>
                <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full bg-info-solid"></span> Théorie / Code</span>
                <span class="inline-flex items-center gap-1"><x-ui.icon name="circle-alert" class="w-3.5 h-3.5 text-danger" /> Conflit</span>
            </p>
        </div>

        @if ($errors->has('starts_at') && ! $editSession)
            <x-ui.alert tone="danger" title="Créneau refusé">{{ $errors->first('starts_at') }}</x-ui.alert>
        @endif
        @if ($errors->has('duplicate'))
            <x-ui.alert tone="danger" title="Duplication impossible">{{ $errors->first('duplicate') }}</x-ui.alert>
        @endif

        {{-- Vue jour --}}
        <div x-show="view === 'day'" class="space-y-3">
            <div class="no-scrollbar flex gap-1 overflow-x-auto">
                <template x-for="d in @js($days->map(fn ($d) => ['value' => $d->toDateString(), 'label' => ucfirst($d->locale('fr')->isoFormat('ddd D')), 'full' => ucfirst($d->locale('fr')->isoFormat('dddd D MMMM'))])->values()->all())" :key="d.value">
                    <button type="button" @click="day = d.value" :aria-pressed="(day === d.value).toString()"
                        :class="day === d.value ? 'bg-accent text-on-accent' : 'bg-solid text-ink-2 hover:text-ink'"
                        class="shrink-0 rounded-pill border border-line px-3 py-1.5 text-[12.5px] font-medium focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                        x-text="d.label"></button>
                </template>
            </div>

            <div class="material-solid overflow-hidden">
                <div x-show="resources.length === 0" class="p-6">
                    <x-ui.empty-state icon="users" title="Aucune ressource à afficher" description="Ajoutez un moniteur ou un véhicule pour retrouver le planning en colonnes." class="py-6" />
                </div>

                <div x-show="resources.length > 0" class="planning-scroll" role="region" aria-label="Planning du jour" tabindex="0">
                    <div class="min-w-max">
                        <div class="sticky top-0 z-20 flex border-b border-line bg-solid">
                            <div class="w-16 shrink-0 border-r border-line px-2 py-2 text-[11px] font-bold uppercase tracking-wide text-ink-3">Heure</div>
                            <template x-for="r in resources" :key="r.id">
                                <div class="w-44 shrink-0 border-r border-line px-2 py-2">
                                    <span class="block truncate text-[12px] font-semibold text-ink" x-text="r.label"></span>
                                </div>
                            </template>
                        </div>

                        <template x-for="slot in slots" :key="slot">
                            <div class="flex border-b border-line/60">
                                <div class="w-16 shrink-0 border-r border-line px-2 py-1 text-right text-[11px] tabular-nums text-ink-3" x-text="slot"></div>
                                <template x-for="r in resources" :key="r.id + '-' + slot">
                                    <div class="h-10 w-44 shrink-0 border-r border-line/60 p-0.5">
                                        <template x-for="s in forCell(r.id, slot)" :key="s.id">
                                            <button type="button" x-on:click="openSession(s)"
                                                class="flex h-full w-full flex-col justify-center overflow-hidden rounded-xs px-1.5 text-left text-[11px] leading-tight focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                                                :class="s.conflict ? 'bg-danger-soft text-danger ring-1 ring-danger' : (s.type_value === 'practical' ? 'bg-accent-soft text-accent hover:bg-accent-soft' : 'bg-info-soft text-info')"
                                                :aria-label="s.student + ' ' + s.start + ' ' + s.end + (s.conflict ? ' conflit' : '')">
                                                <span class="truncate font-semibold" x-text="s.student"></span>
                                                <span class="truncate opacity-80" x-text="s.start + '–' + s.end + (s.vehicle ? ' · ' + s.vehicle : '')"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Liste du jour : équivalent textuel de la grille --}}
                <div class="border-t border-line p-3">
                    <h3 class="text-[13px] font-semibold text-ink">Séances du jour sélectionné</h3>
                    <template x-if="daySessions.length === 0">
                        <p class="mt-1 text-[13px] text-ink-3">Aucune séance ce jour-là. Utilisez « Nouvelle séance » pour en planifier une.</p>
                    </template>
                    <ul class="mt-2 divide-y divide-line">
                        <template x-for="s in daySessions" :key="s.id">
                            <li>
                                <button type="button" x-on:click="openSession(s)" class="flex w-full flex-wrap items-center gap-2 py-2 text-left hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                                    <span class="w-24 shrink-0 text-[12.5px] font-semibold tabular-nums text-ink" x-text="s.start + ' – ' + s.end"></span>
                                    <span class="min-w-0 flex-1 truncate text-[13px] text-ink" x-text="s.type + ' · ' + s.student"></span>
                                    <span class="text-[12px] text-ink-3" x-text="s.instructor"></span>
                                    <span x-show="s.conflict" class="text-[11.5px] font-semibold text-danger">Conflit</span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Vue semaine --}}
        <div x-show="view === 'week'" x-cloak class="space-y-3">
            @foreach ($days as $day)
                @php
                    $daySessions = $list->filter(fn ($s) => $s->scheduled_date?->toDateString() === $day->toDateString())->sortBy('starts_at');
                @endphp
                <section class="material-solid p-4">
                    <h2 class="text-section-title text-ink">
                        {{ ucfirst($day->locale('fr')->isoFormat('dddd D MMMM')) }}
                        @if ($day->isToday())
                            <x-ui.badge tone="accent" class="ml-1">Aujourd’hui</x-ui.badge>
                        @endif
                    </h2>
                    @if ($daySessions->isEmpty())
                        <p class="mt-1 text-[13px] text-ink-3">Aucune séance.</p>
                    @else
                        <ul class="mt-2 divide-y divide-line">
                            @foreach ($daySessions as $session)
                                <li class="flex flex-wrap items-center gap-2 py-2">
                                    <span class="w-24 shrink-0 text-[12.5px] font-semibold tabular-nums text-ink">{{ substr($session->starts_at, 0, 5) }} – {{ substr($session->ends_at, 0, 5) }}</span>
                                    <span class="min-w-0 flex-1 truncate text-[13px] text-ink">{{ $session->type?->label() }} · {{ $session->student?->fullName() }}</span>
                                    <span class="text-[12px] text-ink-3">{{ $session->instructor?->name }}</span>
                                    @if (isset($conflictIds[$session->id]))
                                        <span class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-danger"><x-ui.icon name="circle-alert" class="w-3.5 h-3.5" /> Conflit</span>
                                    @endif
                                    <x-ui.status :value="$session->presence" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>

        {{-- Tiroir de détail de séance --}}
        <template x-teleport="#overlays">
            <div x-show="selected" x-cloak class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-labelledby="session-detail-title" x-trap.inert.noscroll="selected">
                <div class="absolute inset-0 bg-[var(--scrim)]" @click="closeSession()" aria-hidden="true"></div>
                <div class="material-overlay absolute inset-x-0 bottom-0 max-h-[92vh] overflow-y-auto rounded-t-xl sm:inset-y-0 sm:left-auto sm:right-0 sm:h-full sm:w-full sm:max-w-md sm:rounded-l-xl">
                    <template x-if="selected">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-eyebrow">Séance</p>
                                    <h2 id="session-detail-title" class="mt-0.5 text-section-title text-ink" x-text="selected.type + ' · ' + selected.start + ' – ' + selected.end"></h2>
                                </div>
                                <button type="button" @click="closeSession()" class="-m-2 rounded-full p-2 text-ink-2 hover:bg-hover" aria-label="Fermer">
                                    <x-ui.icon name="x" />
                                </button>
                            </div>

                            <dl class="mt-4 space-y-2.5 text-sm">
                                <div><dt class="text-[12px] uppercase tracking-wide text-ink-3">Élève</dt><dd class="text-ink" x-text="selected.student"></dd></div>
                                <div><dt class="text-[12px] uppercase tracking-wide text-ink-3">Moniteur</dt><dd class="text-ink" x-text="selected.instructor"></dd></div>
                                <div><dt class="text-[12px] uppercase tracking-wide text-ink-3">Véhicule</dt><dd class="text-ink" x-text="selected.vehicle || 'Non affecté'"></dd></div>
                                <div><dt class="text-[12px] uppercase tracking-wide text-ink-3">Lieu</dt><dd class="text-ink" x-text="selected.location || '—'"></dd></div>
                                <div><dt class="text-[12px] uppercase tracking-wide text-ink-3">Présence</dt><dd class="text-ink" x-text="selected.presence"></dd></div>
                            </dl>

                            <p x-show="selected.conflict" class="mt-3 flex items-start gap-1.5 rounded-sm bg-danger-soft p-2.5 text-[12.5px] font-medium text-danger">
                                <x-ui.icon name="circle-alert" class="mt-0.5 w-4 h-4" />
                                Conflit de créneau : le moniteur ou le véhicule est déjà occupé sur cet horaire.
                            </p>

                            <div class="mt-5 space-y-4">
                                <form method="POST" :action="urlFor('presence')">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" name="presence" value="present" variant="secondary" icon="circle-check" class="w-full">Marquer présent</x-ui.button>
                                </form>

                                <form method="POST" :action="urlFor('update')">
                                    @csrf
                                    @method('PATCH')
                                    <p class="mb-2 text-[13px] font-semibold text-ink">Déplacer la séance</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <input type="date" name="scheduled_date" x-model="selected.date" required aria-label="Nouvelle date"
                                            class="h-[var(--control-h)] rounded-md border border-control bg-inset px-2 text-[13px] text-ink">
                                        <input type="time" name="starts_at" x-model="selected.start" required aria-label="Nouvelle heure de début" step="1800"
                                            class="h-[var(--control-h)] rounded-md border border-control bg-inset px-2 text-[13px] text-ink">
                                        <input type="time" name="ends_at" x-model="selected.end" required aria-label="Nouvelle heure de fin" step="1800"
                                            class="h-[var(--control-h)] rounded-md border border-control bg-inset px-2 text-[13px] text-ink">
                                    </div>
                                    <input type="hidden" name="student_id" x-model="selected.student_id">
                                    <input type="hidden" name="instructor_id" x-model="selected.instructor_id">
                                    <input type="hidden" name="vehicle_id" x-model="selected.vehicle_id">
                                    <input type="hidden" name="type" x-model="selected.type_value">
                                    <x-ui.button type="submit" variant="secondary" icon="calendar-clock" class="mt-2 w-full">Enregistrer le déplacement</x-ui.button>
                                </form>

                                <form method="POST" :action="urlFor('duplicate')">
                                    @csrf
                                    <p class="mb-2 text-[13px] font-semibold text-ink">Dupliquer</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <input type="date" name="scheduled_date" x-model="selected.date" required aria-label="Date de la copie"
                                            class="h-[var(--control-h)] rounded-md border border-control bg-inset px-2 text-[13px] text-ink">
                                        <input type="time" name="starts_at" x-model="selected.start" required aria-label="Heure de début de la copie" step="1800"
                                            class="h-[var(--control-h)] rounded-md border border-control bg-inset px-2 text-[13px] text-ink">
                                        <input type="time" name="ends_at" x-model="selected.end" required aria-label="Heure de fin de la copie" step="1800"
                                            class="h-[var(--control-h)] rounded-md border border-control bg-inset px-2 text-[13px] text-ink">
                                    </div>
                                    <x-ui.button type="submit" variant="ghost" icon="copy" class="mt-2 w-full">Dupliquer la séance</x-ui.button>
                                </form>

                                <form method="POST" :action="urlFor('destroy')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" class="w-full text-danger" icon="ban">Annuler la séance</x-ui.button>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Création --}}
    @can('create', \App\Domain\Scheduling\Models\LessonSession::class)
        <x-ui.dialog name="new-session" title="Nouvelle séance" description="Le serveur vérifie les conflits de moniteur et de véhicule avant d’enregistrer." :open="$createOpen">
            <x-form.errors />
            <form method="POST" action="{{ route('scheduling.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="student_id" label="Élève" required>
                    <x-form.select name="student_id" required :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->fullName()])->all()" placeholder="Choisir un élève" />
                </x-form.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="instructor_id" label="Moniteur" required>
                        <x-form.select name="instructor_id" required :options="$instructors->pluck('name', 'id')->all()" placeholder="Choisir un moniteur" />
                    </x-form.field>
                    <x-form.field name="vehicle_id" label="Véhicule" hint="Obligatoire pour une séance de conduite.">
                        <x-form.select name="vehicle_id" :options="$vehicles->pluck('plate', 'id')->all()" placeholder="Sans véhicule" />
                    </x-form.field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="type" label="Type de séance" required>
                        <x-form.select name="type" required :options="collect(\App\Domain\Scheduling\Enums\SessionType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" />
                    </x-form.field>
                    <x-form.field name="scheduled_date" label="Date" required>
                        <x-form.input name="scheduled_date" type="date" :value="$defaultDay" required />
                    </x-form.field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="starts_at" label="Début" required>
                        <x-form.input name="starts_at" type="time" step="1800" required />
                    </x-form.field>
                    <x-form.field name="ends_at" label="Fin" required>
                        <x-form.input name="ends_at" type="time" step="1800" required />
                    </x-form.field>
                </div>

                <x-form.field name="location" label="Lieu" optional>
                    <x-form.input name="location" placeholder="Salle A, circuit sud…" />
                </x-form.field>

                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-session' })">Annuler</x-ui.button>
                    <x-ui.button type="submit" icon="calendar">Planifier</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
