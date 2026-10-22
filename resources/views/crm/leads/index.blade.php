@php
    $statuses = collect(\App\Domain\CRM\Enums\LeadStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    $counts = $leads->groupBy(fn ($lead) => $lead->status?->value)->map->count();
@endphp

<x-app-layout :title="'Prospects'">
    <x-layout.page-header
        title="Prospects"
        description="Les contacts entrants : suivez les appels, qualifiez et convertissez en élève."
        :breadcrumbs="[['label' => 'Prospects']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\CRM\Models\Lead::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-lead' })">Nouveau prospect</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-4">
            @foreach (\App\Domain\CRM\Enums\LeadStatus::cases() as $status)
                <x-data.kpi :label="$status->label()" :value="$counts->get($status->value, 0)" icon="sparkles" />
            @endforeach
        </div>

        <x-ui.card variant="solid" padding="p-0">
            @if ($leads->isEmpty())
                <x-ui.empty-state icon="sparkles" title="Aucun prospect" description="Enregistrez les appels et messages reçus pour ne plus perdre de contact." class="py-10">
                    <x-slot:actions>
                        @can('create', \App\Domain\CRM\Models\Lead::class)
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-lead' })">Nouveau prospect</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($leads as $lead)
                        <li class="px-5 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold text-ink">{{ $lead->name }}</p>
                                    <p class="text-[12px] text-ink-3">
                                        {{ $lead->phone ?: '—' }}{{ $lead->email ? ' · '.$lead->email : '' }}{{ $lead->source ? ' · '.$lead->source : '' }}
                                    </p>
                                    @if ($lead->notes)
                                        <p class="mt-1 line-clamp-2 text-[12.5px] text-ink-2">{{ $lead->notes }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.status :value="$lead->status" compact />

                                    @can('update', $lead)
                                        <form method="POST" action="{{ route('crm.leads.status', $lead) }}" class="flex items-center gap-1.5">
                                            @csrf
                                            @method('PATCH')
                                            <label for="status-{{ $lead->id }}" class="sr-only">Statut du prospect</label>
                                            <select id="status-{{ $lead->id }}" name="status" class="h-8 rounded-pill border border-control bg-inset px-2 text-[12px] text-ink">
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected($lead->status?->value === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <x-ui.button type="submit" size="sm" variant="ghost">Mettre à jour</x-ui.button>
                                        </form>
                                    @endcan

                                    @can('update', $lead)
                                        @if ($lead->converted_student_id === null && $lead->status?->value !== 'converted')
                                            <form method="POST" action="{{ route('crm.leads.convert', $lead) }}">
                                                @csrf
                                                <x-ui.button type="submit" size="sm" variant="secondary" icon="arrow-right">Convertir en élève</x-ui.button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    @can('create', \App\Domain\CRM\Models\Lead::class)
        <x-ui.dialog name="new-lead" title="Nouveau prospect">
            <x-form.errors />
            <form method="POST" action="{{ route('crm.leads.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="name" label="Nom" required>
                    <x-form.input name="name" required />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="phone" label="Téléphone" optional>
                        <x-form.input name="phone" type="tel" inputmode="tel" icon="phone" />
                    </x-form.field>
                    <x-form.field name="email" label="Adresse e-mail" optional>
                        <x-form.input name="email" type="email" icon="envelope" />
                    </x-form.field>
                </div>
                <x-form.field name="source" label="Origine du contact" optional>
                    <x-form.input name="source" placeholder="Appel, passage, réseaux sociaux…" />
                </x-form.field>
                <x-form.field name="notes" label="Notes" optional>
                    <x-form.textarea name="notes" />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-lead' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Enregistrer le prospect</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
