<x-app-layout>
    <x-slot name="header">Recyclage</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvelle entrée</div>
            <form id="recyclage-create-form" method="POST" action="{{ route('recyclage.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                <div>
                    <x-input-label for="full_name" value="Nom complet" />
                    <x-text-input id="full_name" name="full_name" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="motif" value="Motif" />
                    <select id="motif" name="motif" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach (\App\Domain\Recyclage\Enums\RecyclageMotif::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('motif')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone" value="Téléphone" />
                    <x-text-input id="phone" name="phone" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="instructor_id" value="Moniteur" />
                    <select id="instructor_id" name="instructor_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">-</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('instructor_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="session_date" value="Date" />
                    <x-text-input id="session_date" type="date" name="session_date" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('session_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="amount" value="Montant" />
                    <x-text-input id="amount" type="number" step="0.01" name="amount" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <x-primary-button>Enregistrer</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Nom</th>
                            <th class="px-5 py-3 font-medium">Motif</th>
                            <th class="px-5 py-3 font-medium">Moniteur</th>
                            <th class="px-5 py-3 font-medium">Montant</th>
                            <th class="px-5 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($entries as $entry)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->session_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->full_name }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->motif->label() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->instructor?->name ?? '-' }}</td>
                                <td class="px-5 py-3 font-medium text-content">{{ number_format($entry->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('recyclage.destroy', $entry) }}" onsubmit="return confirm('Supprimer cette entrée ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger text-sm font-medium">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="6"
                                title="Aucune entrée de recyclage."
                                message="Les entrées enregistrées apparaîtront ici."
                                action="#recyclage-create-form"
                                action-label="Ajouter une entrée"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $entries->links() }}
    </div>
</x-app-layout>
