<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Prospects
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouveau prospect</div>
                <form method="POST" action="{{ route('crm.leads.store') }}" class="grid grid-cols-4 gap-3 items-end">
                    @csrf
                    <x-text-input name="name" placeholder="Nom" class="block w-full" required />
                    <x-text-input name="phone" placeholder="Téléphone" class="block w-full" />
                    <x-text-input name="source" placeholder="Source" class="block w-full" />
                    <x-primary-button>Ajouter</x-primary-button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Téléphone</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($leads as $lead)
                            <tr>
                                <td class="px-4 py-3">{{ $lead->name }}</td>
                                <td class="px-4 py-3">{{ $lead->phone ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('crm.leads.status', $lead) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <select name="status" class="text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md" onchange="this.form.submit()" @disabled($lead->status === \App\Domain\CRM\Enums\LeadStatus::Converted)>
                                            @foreach (\App\Domain\CRM\Enums\LeadStatus::cases() as $case)
                                                <option value="{{ $case->value }}" @selected($lead->status === $case)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($lead->status !== \App\Domain\CRM\Enums\LeadStatus::Converted)
                                        <form method="POST" action="{{ route('crm.leads.convert', $lead) }}" onsubmit="return confirm('Convertir ce prospect en élève ?');">
                                            @csrf
                                            <button type="submit" class="text-xs text-indigo-600 underline">Convertir en élève</button>
                                        </form>
                                    @else
                                        <a href="{{ route('students.show', $lead->converted_student_id) }}" class="text-xs text-indigo-600 underline">Voir l'élève</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun prospect.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
