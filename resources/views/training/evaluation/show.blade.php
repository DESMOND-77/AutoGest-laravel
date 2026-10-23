@php
    $levels = \App\Domain\Training\Enums\SkillLevel::cases();
    $acquired = $progress->filter(fn ($p) => $p->level?->value === 'acquired')->count();
    $total = $skillsByCategory->flatten(1)->count();
    $percent = $total > 0 ? (int) round($acquired / $total * 100) : 0;
@endphp

<x-app-layout :title="'Évaluation — '.$student->fullName()">
    <x-layout.page-header
        title="Évaluation des compétences"
        :description="$student->fullName().' · '.($student->license_category?->value ? 'permis '.$student->license_category->value : '').' · '.$acquired.'/'.$total.' acquise(s)'"
        :breadcrumbs="[
            ['label' => 'Élèves', 'href' => route('students.index')],
            ['label' => $student->fullName(), 'href' => route('students.show', $student)],
            ['label' => 'Évaluation'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('moniteur.eleves.feuille-route', $student)" variant="secondary" icon="clipboard-list">Feuille de route</x-ui-button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-3xl space-y-4">
        <x-ui.card variant="panel">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[12px] uppercase tracking-wide text-ink-3">Compétences acquises</p>
                    <p class="text-2xl font-bold tabular-nums text-ink">{{ $percent }} %</p>
                </div>
                <p class="text-[13px] text-ink-2">{{ $acquired }} / {{ $total }}</p>
            </div>
            <x-ui.progress :value="$percent" label="Progression" class="mt-3" />
        </x-ui.card>

        @if ($total === 0)
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="target" title="Aucune compétence" description="Le référentiel de compétences n’est pas encore configuré dans votre établissement." />
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('training.evaluation.store', $student) }}" class="space-y-4">
                @csrf

                @foreach ($skillsByCategory as $category => $skills)
                    <section class="material-solid overflow-hidden">
                        <header class="border-b border-line px-4 py-2.5">
                            @php $acquiredInCategory = $skills->filter(fn ($s) => ($progress->get($s->id)?->level?->value ?? 'not_started') === 'acquired')->count(); @endphp
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="text-[13.5px] font-semibold text-ink">{{ $category ?: 'Sans catégorie' }}</h2>
                                <span class="text-[12px] text-ink-3">{{ $acquiredInCategory }}/{{ $skills->count() }} acquises</span>
                            </div>
                        </header>
                        <ul class="divide-y divide-line">
                            @foreach ($skills as $skill)
                                @php $current = $progress->get($skill->id)?->level?->value ?? 'not_started'; @endphp
                                <li class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-[13.5px] font-medium text-ink">{{ $skill->label }}</p>
                                            <p class="text-[11.5px] uppercase tracking-wide text-ink-3">{{ $skill->code }}</p>
                                            @if ($current === 'acquired' && $progress->get($skill->id)?->validated_at)
                                                <p class="text-[11.5px] text-ink-3">Validé le {{ $progress->get($skill->id)->validated_at->format('d/m/Y') }}</p>
                                            @endif
                                        </div>
                                        <x-ui.status :value="$current" compact />
                                    </div>

                                    <fieldset class="mt-2">
                                        <legend class="sr-only">Niveau pour {{ $skill->label }}</legend>
                                        <div class="inline-flex w-full max-w-md rounded-pill bg-hover p-0.5">
                                            @foreach ($levels as $level)
                                                <label class="flex-1 cursor-pointer">
                                                    <input type="radio" name="levels[{{ $skill->id }}]" value="{{ $level->value }}" class="peer sr-only" @checked($current === $level->value)>
                                                    <span class="block rounded-pill px-2 py-1.5 text-center text-[12px] font-medium text-ink-2 transition-colors peer-checked:bg-solid peer-checked:text-ink peer-checked:shadow-sm peer-focus-visible:shadow-[var(--focus-ring)]">
                                                        {{ $level->label() }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                <div class="sticky bottom-[calc(var(--dock-h)+8px)] rounded-md border border-line bg-[var(--surface-solid)] p-3 shadow-[var(--elev-2)] lg:bottom-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[12.5px] text-ink-2">L’enregistrement crée une entrée d’audit pour chaque niveau modifié.</p>
                        <x-ui.button type="submit" icon="check">Enregistrer l’évaluation</x-ui.button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
