@php
    $days = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche'];
    $byDay = $instructor->availabilities->groupBy('day_of_week');
@endphp

<x-app-layout :title="$instructor->user?->name ?? 'Moniteur'">
    <x-layout.page-header
        :title="$instructor->user?->name ?? 'Moniteur'"
        :description="($instructor->license_number ? 'Permis '.$instructor->license_number : 'Moniteur').' · embauché le '.($instructor->hire_date?->format('d/m/Y') ?? '—')"
        :breadcrumbs="[['label' => 'Moniteurs', 'href' => route('instructors.index')], ['label' => $instructor->user?->name ?? 'Moniteur']]"
    >
        <x-slot:actions>
            <x-ui.status :value="$instructor->status" />
            <x-ui.button :href="route('scheduling.index', ['instructor_id' => $instructor->user_id])" variant="secondary" icon="calendar">Voir son planning</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-ui.card title="Disponibilités hebdomadaires" description="Créneaux pendant lesquels le moniteur peut être planifié." variant="solid" padding="p-0">
                <div class="divide-y divide-line">
                    @foreach ($days as $dayNumber => $dayLabel)
                        @php $slots = collect($byDay[$dayNumber] ?? [])->sortBy('starts_at'); @endphp
                        <div class="flex flex-wrap items-center gap-2 px-5 py-3 max-sm:px-4">
                            <span class="w-24 shrink-0 text-[13px] font-medium text-ink">{{ $dayLabel }}</span>
                            @if ($slots->isEmpty())
                                <span class="text-[12.5px] text-ink-3">Aucun créneau</span>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($slots as $slot)
                                        <span class="inline-flex items-center gap-1 rounded-pill bg-accent-soft px-2.5 py-0.5 text-[12px] font-medium text-accent">
                                            {{ substr($slot->starts_at, 0, 5) }}–{{ substr($slot->ends_at, 0, 5) }}
                                            @can('update', $instructor)
                                                <form method="POST" action="{{ route('instructors.availabilities.destroy', [$instructor, $slot]) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-full p-0.5 hover:text-danger focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]" aria-label="Supprimer ce créneau">
                                                        <x-ui.icon name="x" class="w-3 h-3" />
                                                    </button>
                                                </form>
                                            @endcan
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            @can('update', $instructor)
                <x-ui.card title="Ajouter un créneau" variant="panel">
                    <form method="POST" action="{{ route('instructors.availabilities.store', $instructor) }}" class="grid gap-3 sm:grid-cols-4 sm:items-end">
                        @csrf
                        <x-form.field name="day_of_week" label="Jour" required>
                            <x-form.select name="day_of_week" :options="$days" :selected="1" />
                        </x-form.field>
                        <x-form.field name="starts_at" label="Début" required>
                            <x-form.input name="starts_at" type="time" value="08:00" step="1800" required />
                        </x-form.field>
                        <x-form.field name="ends_at" label="Fin" required>
                            <x-form.input name="ends_at" type="time" value="12:00" step="1800" required />
                        </x-form.field>
                        <x-ui.button type="submit" icon="plus">Ajouter</x-ui.button>
                    </form>
                    <p class="mt-2 text-[12px] text-ink-3">Les disponibilités servent de repère : le serveur refuse deux séances qui se chevauchent pour un même moniteur.</p>
                </x-ui.card>
            @endcan
        </div>

        <aside class="space-y-4">
            <x-ui.card title="Contact" variant="panel">
                <x-data.description-list :columns="1" :items="[
                    ['label' => 'E-mail', 'value' => $instructor->user?->email ?? '—'],
                    ['label' => 'Compte actif', 'value' => $instructor->user?->is_active ? 'Oui' : 'Non'],
                    ['label' => 'Spécialités', 'value' => ! empty($instructor->specialties) ? implode(', ', $instructor->specialties) : '—'],
                ]" />
            </x-ui.card>

            @can('update', $instructor)
                <x-ui.card title="Modifier la fiche" variant="solid">
                    <form method="POST" action="{{ route('instructors.update', $instructor) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <x-form.field name="license_number" label="Numéro de permis" optional>
                            <x-form.input name="license_number" :value="$instructor->license_number" />
                        </x-form.field>
                        <x-form.field name="hire_date" label="Date d’embauche" optional>
                            <x-form.input name="hire_date" type="date" :value="$instructor->hire_date?->format('Y-m-d')" />
                        </x-form.field>
                        <x-ui.button type="submit" size="sm" variant="secondary">Enregistrer</x-ui.button>
                    </form>
                </x-ui.card>
            @endcan
        </aside>
    </div>
</x-app-layout>
