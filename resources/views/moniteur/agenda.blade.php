<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Mon agenda — semaine du {{ $week->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="flex gap-3 text-sm">
                <a href="{{ route('moniteur.agenda', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="underline">&larr; Semaine précédente</a>
                <a href="{{ route('moniteur.agenda', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="underline">Semaine suivante &rarr;</a>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Horaire</th>
                            <th class="px-4 py-3">Élève</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Présence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="px-4 py-3">{{ $session->scheduled_date->format('d/m') }}</td>
                                <td class="px-4 py-3">{{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('training.evaluation.show', $session->student) }}" class="text-indigo-600 dark:text-indigo-400">
                                        {{ $session->student->fullName() }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $session->type->label() }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('scheduling.presence', $session) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <select name="presence" class="text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md" onchange="this.form.submit()">
                                            @foreach (\App\Domain\Scheduling\Enums\PresenceStatus::cases() as $case)
                                                <option value="{{ $case->value }}" @selected($session->presence === $case)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucune séance cette semaine.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
