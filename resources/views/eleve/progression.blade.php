@php
    $levels = [
        'not_started' => ['label' => 'Non commencé', 'tone' => 'bg-[var(--line-control)]'],
        'in_progress' => ['label' => 'En cours', 'tone' => 'bg-warning-solid'],
        'acquired' => ['label' => 'Acquis', 'tone' => 'bg-success-solid'],
    ];
    $acquired = collect($progress)->filter(fn ($p) => $p->level?->value === 'acquired')->count();
@endphp

<x-app-layout :title="'Ma progression'">
    <x-layout.page-header
        title="Ma progression"
        description="Les compétences validées avec votre moniteur, catégorie par catégorie."
        :breadcrumbs="[['label' => 'Accueil', 'href' => route('eleve.dashboard')], ['label' => 'Progression']]"
    />

    <div class="mx-auto max-w-2xl space-y-4">
        <x-ui.card variant="panel">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[12px] uppercase tracking-wide text-ink-3">Progression globale</p>
                    <p class="text-3xl font-bold tabular-nums text-ink">{{ $overallPercent }} %</p>
                </div>
                <p class="text-[13px] text-ink-2">{{ $acquired }} compétence(s) acquise(s)</p>
            </div>
            <x-ui.progress :value="$overallPercent" label="Progression globale" class="mt-3" />
            <p class="mt-3 text-[12.5px] text-ink-3">
                « En cours » compte pour moitié dans cette moyenne. Seul votre moniteur peut valider une compétence.
            </p>
        </x-ui.card>

        @if ($skillsByCategory->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="target" title="Aucune compétence configurée" description="Votre auto-école n’a pas encore défini de référentiel de compétences." />
            </x-ui.card>
        @else
            @foreach ($skillsByCategory as $category => $skills)
                <section class="material-solid overflow-hidden">
                    <header class="border-b border-line px-4 py-2.5">
                        @php $acquiredInCategory = $skills->filter(fn ($s) => ($progress->get($s->id)?->level?->value ?? 'not_started') === 'acquired')->count(); @endphp
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-[13.5px] font-semibold capitalize text-ink">{{ $category ?: 'Sans catégorie' }}</h2>
                            <span class="text-[12px] text-ink-3">{{ $acquiredInCategory }}/{{ $skills->count() }} acquises</span>
                        </div>
                    </header>
                    <ul class="divide-y divide-line">
                        @foreach ($skills as $skill)
                            @php
                                $level = $progress->get($skill->id)?->level?->value ?? 'not_started';
                                $meta = $levels[$level] ?? $levels['not_started'];
                                $percent = $level === 'acquired' ? 100 : ($level === 'in_progress' ? 50 : 0);
                            @endphp
                            <li class="px-4 py-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[13px] text-ink">{{ $skill->label }}</p>
                                        @if ($level === 'acquired' && $progress->get($skill->id)?->validated_at)
                                            <p class="text-[11.5px] text-ink-3">Validé le {{ $progress->get($skill->id)->validated_at->format('d/m/Y') }}</p>
                                        @endif
                                    </div>
                                    <span class="flex items-center gap-1.5 text-[11.5px] text-ink-3">
                                        <span class="size-2 rounded-full {{ $meta['tone'] }}" aria-hidden="true"></span>
                                        {{ $meta['label'] }}
                                    </span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--surface-inset)]" role="presentation">
                                    <div class="h-full rounded-full {{ $meta['tone'] }}" style="width: {{ max(3, $percent) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
