<x-app-layout>
    <x-slot name="header">{{ $document->original_name }}</x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-4 print:py-0 print:max-w-none">
        <div class="flex items-center justify-between print:hidden">
            <button type="button" onclick="history.back()" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                <x-icon name="chevron-left" class="w-4 h-4" /> Retour
            </button>

            <div class="flex items-center gap-2">
                <x-secondary-button type="button" onclick="document.getElementById('document-frame').contentWindow.print()">
                    Imprimer
                </x-secondary-button>
                <a
                    href="{{ route('documents.download', $document) }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary rounded-ui-md font-semibold text-sm text-primary-content shadow-soft-sm hover:shadow-soft active:shadow-inset focus:outline-none focus-visible:shadow-inset-focus transition"
                >
                    Télécharger
                </a>
            </div>
        </div>

        <x-card class="p-0 overflow-hidden print:shadow-none print:border-0">
            {{-- contentWindow.print() (see the Imprimer button above) prints
                 the file rendered in this frame, not the surrounding page -
                 calling window.print() here would print this whole layout
                 (header, buttons, card chrome) instead of the document. --}}
            <iframe
                id="document-frame"
                src="{{ route('documents.stream', $document) }}"
                title="{{ $document->original_name }}"
                class="w-full border-0 print:h-screen"
                style="height: 80vh;"
            ></iframe>
        </x-card>
    </div>
</x-app-layout>
