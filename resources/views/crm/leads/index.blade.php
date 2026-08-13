<x-app-layout>
    <x-slot name="header">Prospects</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouveau prospect</div>
            <form id="leads-create-form" method="POST" action="{{ route('crm.leads.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                @csrf
                <x-text-input name="name" placeholder="Nom" class="block w-full" required />
                <x-text-input name="phone" placeholder="Téléphone" class="block w-full" />
                <x-text-input name="source" placeholder="Source" class="block w-full" />
                <x-primary-button>Ajouter</x-primary-button>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nom</th>
                            <th class="px-5 py-3 font-medium">Téléphone</th>
                            <th class="px-5 py-3 font-medium">Statut</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($leads as $lead)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content font-medium">{{ $lead->name }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $lead->phone ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('crm.leads.status', $lead) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <select name="status" class="text-xs rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0" onchange="this.form.submit()" @disabled($lead->status === \App\Domain\CRM\Enums\LeadStatus::Converted)>
                                            @foreach (\App\Domain\CRM\Enums\LeadStatus::cases() as $case)
                                                <option value="{{ $case->value }}" @selected($lead->status === $case)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($lead->status !== \App\Domain\CRM\Enums\LeadStatus::Converted)
                                        <form method="POST" action="{{ route('crm.leads.convert', $lead) }}" onsubmit="return confirm('Convertir ce prospect en élève ?');">
                                            @csrf
                                            <button type="submit" class="text-xs text-primary hover:underline">Convertir en élève</button>
                                        </form>
                                    @else
                                        <a href="{{ route('students.show', $lead->converted_student_id) }}" class="text-xs text-primary hover:underline">Voir l'élève</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="4"
                                title="Aucun prospect enregistré."
                                message="Ajoutez un prospect pour commencer à suivre vos demandes d'information."
                                action="#leads-create-form"
                                action-label="Ajouter un prospect"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
