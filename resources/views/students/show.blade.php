<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $student->fullName() }}
            </h2>
            @can('update', $student)
                <a href="{{ route('students.edit', $student) }}" class="text-sm underline text-gray-600 dark:text-gray-400">
                    Modifier
                </a>
            @endcan
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
        </div>
    </div>
</x-app-layout>
