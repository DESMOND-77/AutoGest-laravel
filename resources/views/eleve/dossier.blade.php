<x-app-layout>
    <x-slot name="header">Mon dossier</x-slot>

    <div class="py-6 space-y-4 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->has('dossier'))
            <x-alert variant="danger">{{ $errors->first('dossier') }}</x-alert>
        @endif

        <x-card>
            <div class="divide-y divide-surface-inset">
                @forelse ($types as $type)
                    @php($document = $documentsByType->get($type->id))
                    <div class="py-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-content">{{ $type->label }}</span>
                            @if ($document)
                                <span @class([
                                    'text-xs font-semibold px-2 py-0.5 rounded-full',
                                    'bg-warning/10 text-warning' => $document->review_status->value === 'pending',
                                    'bg-success/10 text-success' => $document->review_status->value === 'approved',
                                    'bg-danger/10 text-danger' => $document->review_status->value === 'rejected',
                                ])>{{ $document->review_status->label() }}</span>
                            @else
                                <span class="text-xs text-content-muted">Rien déposé</span>
                            @endif
                        </div>

                        @if ($document?->review_status->value === 'rejected')
                            <p class="text-xs text-danger mt-1">{{ $document->rejection_reason }}</p>
                        @endif

                        <form method="POST" action="{{ route('eleve.dossier.upload', $type) }}" enctype="multipart/form-data" class="mt-2 flex gap-2">
                            @csrf
                            <input type="file" name="file" class="text-xs flex-1" required>
                            <x-primary-button class="text-xs">{{ $document ? 'Redéposer' : 'Déposer' }}</x-primary-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-content-secondary py-3">Aucune pièce requise pour le moment.</p>
                @endforelse
            </div>
        </x-card>

        <form method="POST" action="{{ route('eleve.dossier.submit') }}">
            @csrf
            <x-primary-button class="w-full justify-center" @disabled(! $canSubmit)>
                Soumettre mon dossier
            </x-primary-button>
        </form>
    </div>
</x-app-layout>
