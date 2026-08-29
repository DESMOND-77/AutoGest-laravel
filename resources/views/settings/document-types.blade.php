<x-app-layout>
    <x-slot name="header">Pièces requises</x-slot>

    <div class="py-6 space-y-6 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <h2 class="text-sm font-semibold text-content mb-3">Ajouter une pièce</h2>
            <form method="POST" action="{{ route('settings.document-types.store') }}" class="flex gap-3">
                @csrf
                <x-text-input name="label" class="flex-1" placeholder="Ex. Carte d'identité" required />
                <x-primary-button>Ajouter</x-primary-button>
            </form>
        </x-card>

        <x-card>
            <h2 class="text-sm font-semibold text-content mb-3">Pièces configurées</h2>
            <div class="divide-y divide-surface-inset">
                @forelse ($types as $type)
                    <div class="flex items-center justify-between py-3">
                        <span class="text-sm text-content {{ $type->is_active ? '' : 'line-through text-content-muted' }}">
                            {{ $type->label }}
                        </span>
                        <form method="POST" action="{{ route('settings.document-types.update', $type) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $type->is_active ? '0' : '1' }}">
                            <button type="submit" class="text-xs text-primary hover:underline">
                                {{ $type->is_active ? 'Désactiver' : 'Réactiver' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-content-secondary py-3">Aucune pièce configurée pour le moment.</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
