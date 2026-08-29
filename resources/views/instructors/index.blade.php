<x-app-layout>
    <x-slot name="header">Moniteurs</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        @can('create', \App\Domain\Instructors\Models\Instructor::class)
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Nouveau moniteur</div>
                <form id="instructors-create-form" method="POST" action="{{ route('instructors.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Nom complet" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="email" value="E-mail" />
                        <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="license_number" value="N° agrément" />
                        <x-text-input id="license_number" name="license_number" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="hire_date" value="Date d'embauche" />
                        <x-text-input id="hire_date" type="date" name="hire_date" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
                <p class="text-xs text-content-muted mt-3">
                    Le nouveau compte moniteur reçoit un e-mail avec un lien pour définir son mot de passe.
                </p>
            </x-card>
        @endcan

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nom</th>
                            <th class="px-5 py-3 font-medium">N° agrément</th>
                            <th class="px-5 py-3 font-medium">Statut</th>
                            <th class="px-5 py-3 font-medium">Embauche</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($instructors as $instructor)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('instructors.show', $instructor) }}" class="font-medium text-content hover:text-primary transition">
                                        {{ $instructor->user->name }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-content-secondary">{{ $instructor->license_number ?? '-' }}</td>
                                <td class="px-5 py-3"><x-badge variant="info">{{ $instructor->status->label() }}</x-badge></td>
                                <td class="px-5 py-3 text-content-secondary">{{ optional($instructor->hire_date)->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="4"
                                title="Aucun moniteur enregistré."
                                message="Ajoutez votre premier moniteur pour pouvoir lui assigner des séances."
                                :action="Auth::user()->can('create', \App\Domain\Instructors\Models\Instructor::class) ? '#instructors-create-form' : null"
                                action-label="Ajouter un moniteur"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
