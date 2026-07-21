<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $student->fullName() }}
            </h2>
            <div class="flex gap-4">
                @can('evaluate', $student)
                    <a href="{{ route('training.evaluation.show', $student) }}" class="text-sm underline text-gray-600 dark:text-gray-400">
                        Évaluer
                    </a>
                @endcan
                @can('create', \App\Domain\Finance\Models\Invoice::class)
                    <a href="{{ route('finance.invoices.create', $student) }}" class="text-sm underline text-gray-600 dark:text-gray-400">
                        Nouvelle facture
                    </a>
                @endcan
                @can('update', $student)
                    <a href="{{ route('students.edit', $student) }}" class="text-sm underline text-gray-600 dark:text-gray-400">
                        Modifier
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 text-red-800 text-sm rounded-md p-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Catégorie</span><br>{{ $student->license_category->value }}</div>
                <div><span class="text-gray-500">Type de cours</span><br>{{ $student->course_type->label() }}</div>
                <div><span class="text-gray-500">Dossier</span><br>{{ $student->dossier_status->label() }}</div>
                <div><span class="text-gray-500">Moniteur</span><br>{{ $student->instructor?->name ?? '—' }}</div>
                <div><span class="text-gray-500">Téléphone</span><br>{{ $student->phone ?? '—' }}</div>
                <div><span class="text-gray-500">E-mail</span><br>{{ $student->email ?? '—' }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm text-gray-500 mb-2">Étape du cycle de vie</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    {{ $student->lifecycle_stage->label() }}
                </div>

                @can('update', $student)
                    @php $next = $student->lifecycle_stage->allowedNextStages(); @endphp
                    @if (count($next))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($next as $stage)
                                <form method="POST" action="{{ route('students.stage', $student) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="stage" value="{{ $stage->value }}">
                                    <button type="submit" class="text-sm bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-md">
                                        &rarr; {{ $stage->label() }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Fin de parcours.</p>
                    @endif
                @endcan
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Documents</div>

                @php
                    $documents = \App\Domain\Documents\Models\Document::query()
                        ->where('documentable_type', $student->getMorphClass())
                        ->where('documentable_id', $student->id)
                        ->where('is_current', true)
                        ->latest()
                        ->get();
                @endphp

                <ul class="text-sm divide-y divide-gray-100 dark:divide-gray-700 mb-4">
                    @forelse ($documents as $document)
                        <li class="py-2 flex justify-between items-center">
                            <span>{{ $document->type->label() }} — {{ $document->original_name }} (v{{ $document->version }})</span>
                            <a href="{{ route('documents.download', $document) }}" class="text-xs text-indigo-600 underline">Télécharger</a>
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">Aucun document.</li>
                    @endforelse
                </ul>

                @can('create', \App\Domain\Documents\Models\Document::class)
                    <form method="POST" action="{{ route('students.documents.store', $student) }}" enctype="multipart/form-data" class="grid grid-cols-3 gap-3 items-end">
                        @csrf
                        <select name="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block w-full">
                            @foreach (\App\Domain\Documents\Enums\DocumentType::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        <input type="file" name="file" class="text-sm" required>
                        <x-primary-button>Déposer</x-primary-button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
