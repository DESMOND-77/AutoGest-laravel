<x-app-layout>
    <x-slot name="header">Dossiers en attente de revue</x-slot>

    <div class="py-6 space-y-4 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @forelse ($students as $student)
            <x-card>
                <h2 class="text-sm font-semibold text-content mb-3">{{ $student->fullName() }}</h2>
                <div class="divide-y divide-surface-inset">
                    @foreach ($student->documents as $document)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-content">{{ $document->requiredDocumentType?->label }}</p>
                                <p class="text-xs text-content-muted">{{ $document->original_name }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('documents.approve', $document) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-success hover:underline">Approuver</button>
                                </form>
                                <form method="POST" action="{{ route('documents.reject', $document) }}" onsubmit="return confirm('Motif du rejet ?');">
                                    @csrf
                                    <input type="hidden" name="reason" value="Document non conforme">
                                    <button type="submit" class="text-xs font-semibold text-danger hover:underline">Rejeter</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @empty
            <x-card>
                <p class="text-sm text-content-secondary">Aucun dossier en attente de revue.</p>
            </x-card>
        @endforelse
    </div>
</x-app-layout>
