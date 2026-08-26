<x-app-layout>
    <x-slot name="header">{{ $document->original_name }}</x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-4 print:py-0 print:max-w-none">
        <div class="flex items-center justify-between print:hidden">
            <button type="button" onclick="history.back()" class="text-sm text-primary hover:underline">
                &larr; Retour
            </button>

            <div class="flex items-center gap-2">
                <x-secondary-button type="button" onclick="window.print()">Imprimer</x-secondary-button>
                <a
                    href="{{ route('documents.download', $document) }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary rounded-ui-md font-semibold text-sm text-primary-content shadow-soft-sm hover:shadow-soft active:shadow-inset focus:outline-none focus-visible:shadow-inset-focus transition"
                >
                    Télécharger
                </a>
            </div>
        </div>

        <x-card class="p-0 overflow-hidden print:shadow-none print:border-0">
            <iframe
                src="{{ route('documents.stream', $document) }}"
                title="{{ $document->original_name }}"
                class="w-full border-0 print:h-screen"
                style="height: 80vh;"
            ></iframe>
        </x-card>
    </div>
</x-app-layout>
