@php
    $byCategory = $skills->groupBy(fn ($skill) => $skill->category ?: 'Sans catégorie');
@endphp

<x-app-layout :title="'Compétences'">
    <x-layout.page-header
        title="Référentiel de compétences"
        description="Les compétences évaluées en conduite. Elles composent la progression de chaque élève."
        :breadcrumbs="[['label' => 'Compétences']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Training\Models\Skill::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-skill' })">Nouvelle compétence</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    @if ($skills->isEmpty())
        <x-ui.card variant="solid">
            <x-ui.empty-state icon="target" title="Aucune compétence" description="Créez votre référentiel (freinage, insertion, stationnement…) pour évaluer les élèves séance après séance." class="py-10">
                <x-slot:actions>
                    @can('create', \App\Domain\Training\Models\Skill::class)
                        <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-skill' })">Nouvelle compétence</x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($byCategory as $category => $items)
                <section class="material-solid overflow-hidden">
                    <header class="flex items-center justify-between gap-2 border-b border-line px-4 py-2.5">
                        <h2 class="text-[13.5px] font-semibold text-ink">{{ $category }}</h2>
                        <span class="text-[12px] text-ink-3">{{ $items->count() }}</span>
                    </header>
                    <ul class="divide-y divide-line">
                        @foreach ($items as $skill)
                            <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] text-ink">{{ $skill->label }}</p>
                                    <p class="text-[11.5px] uppercase tracking-wide text-ink-3">{{ $skill->code }}</p>
                                </div>
                                @can('delete', $skill)
                                    <form method="POST" action="{{ route('training.skills.destroy', $skill) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="ghost" class="text-danger" icon="trash-2" label="Supprimer {{ $skill->label }}" />
                                    </form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif

    @can('create', \App\Domain\Training\Models\Skill::class)
        <x-ui.dialog name="new-skill" title="Nouvelle compétence" description="Le code est un repère court (ex. C1, C2) utilisé dans les fiches d’évaluation.">
            <x-form.errors />
            <form method="POST" action="{{ route('training.skills.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="code" label="Code" required>
                        <x-form.input name="code" placeholder="C1" required />
                    </x-form.field>
                    <x-form.field name="category" label="Catégorie" optional>
                        <x-form.input name="category" placeholder="Maîtrise du véhicule" />
                    </x-form.field>
                </div>
                <x-form.field name="label" label="Libellé" required>
                    <x-form.input name="label" placeholder="Démarrer et s’arrêter en douceur" required />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-skill' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Créer la compétence</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
