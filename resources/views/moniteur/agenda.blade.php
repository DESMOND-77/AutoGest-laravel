<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Mon agenda — semaine du {{ $week->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="flex gap-3 text-sm">
                <a href="{{ route('moniteur.agenda', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="underline">&larr; Semaine précédente</a>
                <a href="{{ route('moniteur.agenda', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="underline">Semaine suivante &rarr;</a>
            </div>

            @if ($sessions->isEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-10 text-center">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Aucune séance cette semaine.</p>
                </div>
            @else
                <x-planning-grid :sessions="$sessions" :week="$week" :show-instructor="false" />
            @endif
        </div>
    </div>
</x-app-layout>
