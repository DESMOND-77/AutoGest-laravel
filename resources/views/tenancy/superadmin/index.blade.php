<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Établissements
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex gap-2 text-sm">
                <a href="{{ route('superadmin.structures.index') }}"
                   class="px-3 py-1 rounded-md {{ $currentStatus ? 'bg-gray-200 dark:bg-gray-700' : 'bg-indigo-600 text-white' }}">
                    Tous
                </a>
                @foreach ($statuses as $status)
                    <a href="{{ route('superadmin.structures.index', ['status' => $status->value]) }}"
                       class="px-3 py-1 rounded-md {{ $currentStatus === $status->value ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700' }}">
                        {{ $status->value }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">E-mail</th>
                            <th class="px-4 py-3">Utilisateurs</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($structures as $structure)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $structure->name }}</td>
                                <td class="px-4 py-3">{{ $structure->email }}</td>
                                <td class="px-4 py-3">{{ $structure->users_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700">
                                        {{ $structure->status->value }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($statuses as $status)
                                            @unless ($status === $structure->status)
                                                <form method="POST" action="{{ route('superadmin.structures.status', $structure) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $status->value }}">
                                                    <button type="submit" class="underline text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $status->value }}
                                                    </button>
                                                </form>
                                            @endunless
                                        @endforeach
                                        <form method="POST" action="{{ route('superadmin.structures.destroy', $structure) }}"
                                              onsubmit="return confirm('Supprimer définitivement cet établissement et toutes ses données ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="underline text-xs text-red-600">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun établissement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{ $structures->links() }}
        </div>
    </div>
</x-app-layout>
