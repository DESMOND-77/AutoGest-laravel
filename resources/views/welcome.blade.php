@php
    $hero = config('landing.hero');
    $modules = config('landing.modules', []);
    $audiences = config('landing.audiences', []);
    $why = config('landing.why', []);
    $steps = config('landing.steps', []);
    $pricing = config('landing.pricing', []);
    $comparison = config('landing.comparison', []);
    $faq = config('landing.faq', []);
    $contact = config('landing.contact', []);
    $phases = config('ui-status.phases', []);
@endphp

<x-public-layout :title="$hero['eyebrow'] ?? null" :description="$hero['subtitle'] ?? null">
    {{-- Héros --}}
    <section class="relative overflow-hidden border-b border-line/60">
        <div class="pointer-events-none absolute -right-24 -top-32 size-[420px] rounded-full bg-accent-soft blur-2xl" aria-hidden="true"></div>
        <div class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:items-center lg:py-20">
            <div>
                <p class="inline-flex items-center gap-1.5 rounded-pill border border-accent-line bg-accent-soft px-3 py-1 text-[12px] font-semibold text-accent">
                    <x-ui.icon name="sparkles" class="w-3.5 h-3.5" />
                    {{ $hero['eyebrow'] }}
                </p>
                <h1 class="mt-4 max-w-xl text-[2.35rem] font-bold leading-[1.08] tracking-[-0.03em] text-ink sm:text-[3.1rem]">
                    {{ $hero['title'] }}
                </h1>
                <p class="mt-4 max-w-xl text-[15.5px] leading-relaxed text-ink-2">{{ $hero['subtitle'] }}</p>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <x-ui.button :href="route('register')" size="lg" icon-right="arrow-right">{{ $hero['primary_cta'] }}</x-ui.button>
                    <x-ui.button :href="route('login')" size="lg" variant="secondary" icon="play">{{ $hero['secondary_cta'] }}</x-ui.button>
                </div>

                <dl class="mt-9 grid max-w-lg grid-cols-3 gap-3">
                    @foreach ($hero['stats'] as $stat)
                        <div class="material-panel p-3">
                            <dt class="sr-only">{{ $stat['label'] }}</dt>
                            <dd>
                                <span class="block text-2xl font-bold tabular-nums text-ink">{{ $stat['value'] }}</span>
                                <span class="mt-0.5 block text-[12px] leading-snug text-ink-3">{{ $stat['label'] }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Aperçu produit : reproduction statique, sans capture d'écran --}}
            <div class="relative">
                <div class="material-panel overflow-hidden p-0">
                    <div class="flex items-center gap-1.5 border-b border-line px-3 py-2.5">
                        <span class="size-2.5 rounded-full bg-[var(--line-control)]"></span>
                        <span class="size-2.5 rounded-full bg-[var(--line-control)]"></span>
                        <span class="size-2.5 rounded-full bg-[var(--line-control)]"></span>
                        <span class="ml-2 truncate rounded-pill bg-[var(--surface-inset)] px-2.5 py-0.5 text-[11px] text-ink-3">auto-gestboard · tableau de bord</span>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-3">À traiter</p>
                                <p class="text-[13px] font-semibold text-ink">3 dossiers · 2 impayés</p>
                            </div>
                            <span class="rounded-pill bg-accent px-2.5 py-1 text-[11px] font-semibold text-on-accent">Aujourd’hui</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([['Élèves', '128'], ['Séances', '17'], ['Encaissé', '1,2 M']] as [$label, $value])
                                <div class="rounded-md bg-[var(--surface-inset)] p-2.5">
                                    <p class="text-[10.5px] text-ink-3">{{ $label }}</p>
                                    <p class="text-[15px] font-bold tabular-nums text-ink">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="rounded-md border border-line p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-3">Planning du jour</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ([['08:00', 'Conduite · A. Mbadinga', 'LB-123-AB'], ['10:30', 'Code · groupe B', 'Salle 1'], ['14:00', 'Conduite · S. Nzamba', 'LB-456-CD']] as [$time, $label, $meta])
                                    <li class="flex items-center gap-2 text-[12px]">
                                        <span class="w-11 shrink-0 font-semibold tabular-nums text-ink">{{ $time }}</span>
                                        <span class="min-w-0 flex-1 truncate text-ink-2">{{ $label }}</span>
                                        <span class="shrink-0 text-ink-3">{{ $meta }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="rounded-md bg-[var(--surface-inset)] p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-3">Parcours élève</p>
                            <div class="mt-2 flex items-center gap-1">
                                @foreach (['Inscription', 'Dossier', 'Code', 'Conduite', 'Permis'] as $i => $phase)
                                    <span class="flex-1">
                                        <span class="block h-1.5 rounded-full {{ $i < 3 ? 'bg-accent' : 'bg-[var(--line-control)]' }}"></span>
                                        <span class="mt-1 block truncate text-[9.5px] {{ $i < 3 ? 'text-ink' : 'text-ink-3' }}">{{ $phase }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-center text-[11px] text-ink-3">Aperçu de l’interface — données d’illustration.</p>
            </div>
        </div>
    </section>

    {{-- Publics --}}
    <section id="publics" class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
        <p class="text-eyebrow">Pour qui</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Chaque rôle voit ce dont il a besoin</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            @foreach ($audiences as $audience)
                <article class="material-panel p-5">
                    <span class="grid size-10 place-items-center rounded-md bg-accent-soft text-accent">
                        <x-ui.icon :name="$audience['icon']" class="w-5 h-5" />
                    </span>
                    <h3 class="mt-3 text-section-title text-ink">{{ $audience['title'] }}</h3>
                    <p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-2">{{ $audience['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Modules --}}
    <section id="modules" class="border-y border-line/60 bg-[var(--surface-panel)]/40">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
            <p class="text-eyebrow">Modules</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Huit modules qui se parlent</h2>
            <p class="mt-2 max-w-2xl text-[14.5px] text-ink-2">
                Pas d’outil séparé : une facture met à jour la caisse, une séance marquée met à jour la progression, une échéance véhicule déclenche une alerte.
            </p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($modules as $module)
                    <article class="material-solid p-4">
                        <span class="grid size-9 place-items-center rounded-md bg-[var(--surface-inset)] text-ink-2">
                            <x-ui.icon :name="$module['icon']" class="w-[18px] h-[18px]" />
                        </span>
                        <h3 class="mt-2.5 text-[14px] font-semibold text-ink">{{ $module['title'] }}</h3>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $module['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Parcours élève --}}
    <section id="parcours" class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
        <p class="text-eyebrow">Parcours</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">15 étapes, 5 phases, aucun dossier perdu</h2>
        <p class="mt-2 max-w-2xl text-[14.5px] text-ink-2">
            Du prospect au permis obtenu, chaque élève avance sur un parcours balisé. Vous voyez toujours où il en est et ce qu’il reste à faire.
        </p>
        <ol class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($phases as $phase)
                <li class="material-panel p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-accent">{{ $phase['label'] }}</span>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($phase['stages'] as $stage)
                            <li class="flex items-start gap-1.5 text-[12.5px] text-ink-2">
                                <x-ui.icon name="circle" class="mt-1 w-2.5 h-2.5 shrink-0 text-ink-3" />
                                {{ \App\Domain\Students\Enums\LifecycleStage::tryFrom($stage)?->label() ?? $stage }}
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Pourquoi --}}
    <section class="border-y border-line/60 bg-[var(--surface-panel)]/40">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
            <p class="text-eyebrow">Pourquoi Auto-GestBoard</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Conçu sur le terrain, pas en théorie</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($why as $item)
                    <article class="flex gap-3">
                        <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-md bg-accent-soft text-accent">
                            <x-ui.icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                        </span>
                        <div>
                            <h3 class="text-[14px] font-semibold text-ink">{{ $item['title'] }}</h3>
                            <p class="mt-0.5 text-[13px] leading-relaxed text-ink-2">{{ $item['description'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Mise en route --}}
    <section class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
        <p class="text-eyebrow">Mise en route</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Opérationnel en quatre étapes</h2>
        <ol class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($steps as $index => $step)
                <li class="material-solid p-4">
                    <span class="grid size-8 place-items-center rounded-full bg-accent text-[13px] font-bold text-on-accent">{{ $index + 1 }}</span>
                    <h3 class="mt-2.5 text-[14px] font-semibold text-ink">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $step['description'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Tarifs --}}
    <section id="tarifs" class="border-y border-line/60 bg-[var(--surface-panel)]/40">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
            <p class="text-eyebrow">Tarifs</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Un abonnement, pas de surprise</h2>
            <p class="mt-2 text-[14.5px] text-ink-2">Prix en francs CFA. Aucun frais d’installation.</p>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                @foreach ($pricing as $plan)
                    <article class="flex flex-col p-5 {{ $plan['featured'] ? 'material-solid ring-2 ring-accent' : 'material-panel' }}">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-section-title text-ink">{{ $plan['name'] }}</h3>
                            @if ($plan['featured'])
                                <x-ui.badge tone="accent">Recommandé</x-ui.badge>
                            @endif
                        </div>
                        <p class="mt-1 text-[12.5px] text-ink-2">{{ $plan['description'] }}</p>
                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold tracking-tight text-ink">{{ $plan['price'] }}</span>
                            @if ($plan['period'])
                                <span class="text-[12.5px] text-ink-3">{{ $plan['period'] }}</span>
                            @endif
                        </p>
                        <ul class="mt-4 flex-1 space-y-1.5">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-start gap-1.5 text-[13px] text-ink-2">
                                    <x-ui.icon name="check" class="mt-0.5 w-4 h-4 shrink-0 text-success" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                        <x-ui.button :href="route('register')" class="mt-5 w-full" :variant="$plan['featured'] ? 'primary' : 'secondary'">{{ $plan['cta'] }}</x-ui.button>
                    </article>
                @endforeach
            </div>

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[36rem] border-collapse text-left text-[13px]">
                    <caption class="sr-only">Comparaison des forfaits</caption>
                    <thead>
                        <tr class="border-b border-line">
                            <th scope="col" class="py-2 pr-4 font-semibold text-ink">Inclus</th>
                            @foreach ($pricing as $plan)
                                <th scope="col" class="px-3 py-2 text-center font-semibold text-ink">{{ $plan['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison as $row)
                            <tr class="border-b border-line/60">
                                <th scope="row" class="py-2 pr-4 text-left font-medium text-ink-2">{{ $row[0] }}</th>
                                @foreach (array_slice($row, 1) as $value)
                                    <td class="px-3 py-2 text-center">
                                        @if (is_bool($value))
                                            @if ($value)
                                                <x-ui.icon name="check" class="mx-auto w-4 h-4 text-success" label="Inclus" />
                                            @else
                                                <span class="text-ink-3" aria-label="Non inclus">—</span>
                                            @endif
                                        @else
                                            <span class="text-ink">{{ $value }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="mx-auto w-full max-w-3xl px-4 py-14 sm:px-6">
        <p class="text-eyebrow">Questions fréquentes</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink sm:text-3xl">Ce que les gérants demandent</h2>
        <div class="mt-6 space-y-2">
            @foreach ($faq as $item)
                <details class="material-solid group p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-[14px] font-semibold text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                        {{ $item['question'] }}
                        <x-ui.icon name="chevron-down" class="w-4 h-4 shrink-0 text-ink-3 transition-transform group-open:rotate-180" />
                    </summary>
                    <p class="mt-2 text-[13.5px] leading-relaxed text-ink-2">{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Appel à l'action --}}
    <section class="border-t border-line/60">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
            <div class="material-panel flex flex-col items-start justify-between gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-ink">Prêt à reprendre la main sur votre auto-école ?</h2>
                    <p class="mt-1.5 max-w-xl text-[14px] text-ink-2">
                        Créez votre établissement en quelques minutes. Aucune carte bancaire n’est demandée pour commencer.
                    </p>
                    <p class="mt-2 text-[12.5px] text-ink-3">
                        Une question ? <a href="mailto:{{ $contact['email'] }}" class="font-medium text-accent hover:underline">{{ $contact['email'] }}</a>
                        · {{ $contact['location'] }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button :href="route('register')" size="lg">Créer mon établissement</x-ui.button>
                    <x-ui.button :href="route('login')" size="lg" variant="secondary">J’ai déjà un compte</x-ui.button>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
