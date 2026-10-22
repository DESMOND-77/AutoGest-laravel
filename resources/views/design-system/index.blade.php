@php
    $themes = \App\Support\Ui\Theme::cases();
    $accents = ['indigo' => 'Indigo', 'violet' => 'Violet', 'rose' => 'Rose', 'orange' => 'Orange', 'amber' => 'Ambre', 'emerald' => 'Émeraude', 'teal' => 'Sarcelle', 'sky' => 'Ciel'];
    $sections = [
        'fondations' => 'Fondations',
        'primitives' => 'Primitives',
        'formulaires' => 'Formulaires',
        'donnees' => 'Données',
        'domaine' => 'Domaine',
        'overlays' => 'Surcouches',
        'etats' => 'États',
    ];
    $demo = [
        ['id' => 1, 'name' => 'Awa Nguema', 'stage' => 'theory_course', 'due' => 12000],
        ['id' => 2, 'name' => 'Jean-Marc Obame', 'stage' => 'practical_course', 'due' => 0],
        ['id' => 3, 'name' => 'Sylvie Mba', 'stage' => 'practical_exam', 'due' => 45000],
    ];

    $demoStudent = new class {
        public function fullName(): string { return 'Sylvie Mba'; }
    };
    $demoSession = new class {
        public string $starts_at = '2026-10-22 08:00:00';
        public string $ends_at = '2026-10-22 09:00:00';
        public $type;
        public $presence;
        public $student;
        public ?string $location = 'Piste principale';
        public $vehicle = null;
    };
    $demoSession->type = \App\Domain\Scheduling\Enums\SessionType::Practical;
    $demoSession->presence = \App\Domain\Scheduling\Enums\PresenceStatus::Planned;
    $demoSession->student = $demoStudent;

    $demoInvoice = new class {
        public float $amount_due = 45000;
        public float $amount_paid = 20000;
        public function balanceDue(): float { return $this->amount_due - $this->amount_paid; }
    };

    $demoVehicle = (new \App\Domain\Fleet\Models\Vehicle)->forceFill([
        'id' => 1,
        'plate' => 'LB-4471',
        'brand' => 'Toyota',
        'model' => 'Corolla',
        'year' => 2019,
        'category' => 'Berline',
        'mileage' => 84000,
        'status' => \App\Domain\Fleet\Enums\VehicleStatus::Active,
        'insurance_expires_at' => now()->addDays(9)->toDateString(),
        'technical_inspection_expires_at' => now()->addDays(45)->toDateString(),
    ]);
@endphp

<x-app-layout title="Design system">
    <x-layout.page-header
        title="Design system Auto-GestBoard"
        description="Guide vivant et banc d’essai visuel : chaque composant dans tous ses états, dans les 6 thèmes et les 2 densités. Page réservée à l’environnement local."
        :breadcrumbs="[['label' => 'Design system']]"
    >
        <x-slot:actions>
            <x-ui.badge tone="warning">Local uniquement</x-ui.badge>
            <x-ui.button :href="route('design-system.index', ['section' => 'fondations'])" variant="secondary" icon="palette">Fondations</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card variant="solid" class="mb-5" title="Réglages du banc d’essai" description="Les sélecteurs écrivent dans le store `prefs` : thème, accent, densité et mode léger.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <fieldset>
                <legend class="text-[12.5px] font-semibold text-ink">Thème</legend>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button type="button" class="rounded-pill border border-line px-2.5 py-1 text-[12px]" :class="$store.prefs.theme === 'auto' && 'border-accent text-accent'" x-on:click="$store.prefs.set('theme', 'auto')">Auto</button>
                    @foreach ($themes as $theme)
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-pill border border-line px-2.5 py-1 text-[12px]" :class="$store.prefs.theme === @js($theme->value) && 'border-accent text-accent'" x-on:click="$store.prefs.set('theme', @js($theme->value))">
                            <span class="size-3 rounded-full theme-swatch theme-swatch-{{ $theme->value }}" aria-hidden="true"></span>{{ $theme->label() }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-[12.5px] font-semibold text-ink">Accent</legend>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button type="button" class="grid size-7 place-items-center rounded-full border border-line text-[10px]" x-on:click="$store.prefs.set('accent', null)" title="Accent du thème">Auto</button>
                    @foreach ($accents as $key => $label)
                        <button type="button" class="grid size-7 place-items-center rounded-full border border-line" x-on:click="$store.prefs.set('accent', @js($key))" :class="$store.prefs.accent === @js($key) && 'ring-2 ring-accent ring-offset-2 ring-offset-[var(--surface-solid)]'" aria-label="Accent {{ $label }}">
                            <span class="size-4 rounded-full accent-swatch-{{ $key }}" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-[12.5px] font-semibold text-ink">Densité</legend>
                <div class="mt-2 inline-flex rounded-pill bg-hover p-0.5">
                    <button type="button" class="rounded-pill px-3 py-1 text-[12px] font-medium" :class="$store.prefs.density === 'comfortable' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('density', 'comfortable')">Confort</button>
                    <button type="button" class="rounded-pill px-3 py-1 text-[12px] font-medium" :class="$store.prefs.density === 'compact' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('density', 'compact')">Compact</button>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-[12.5px] font-semibold text-ink">Performance</legend>
                <div class="mt-2 inline-flex rounded-pill bg-hover p-0.5">
                    @foreach (['auto' => 'Auto', 'full' => 'Complet', 'lite' => 'Léger'] as $perfKey => $perfLabel)
                        <button type="button" class="rounded-pill px-3 py-1 text-[12px] font-medium" :class="$store.prefs.perf === @js($perfKey) ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('perf', @js($perfKey))">{{ $perfLabel }}</button>
                    @endforeach
                </div>
            </fieldset>
        </div>
    </x-ui.card>

    <x-ui.tabs class="mb-5" variant="pill" :current="$section" :items="collect($sections)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'href' => route('design-system.index', ['section' => $key])])->values()->all()" label="Familles de composants" />

    @if ($section === 'fondations')
        <div class="space-y-4">
            <x-ui.card title="Palette sémantique" description="Toujours utiliser ces jetons, jamais une couleur brute." variant="panel">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ([
                        ['--surface-solid', 'surface-solid', 'Cartes opaques'],
                        ['--surface-panel', 'surface-panel', 'Panneaux de verre'],
                        ['--surface-glass', 'surface-glass', 'Barres et en-têtes'],
                        ['--surface-inset', 'surface-inset', 'Champs de saisie'],
                        ['--accent', 'accent', 'Action principale'],
                        ['--accent-soft', 'accent-soft', 'Fond accentué'],
                        ['--text-1', 'text-1', 'Texte principal'],
                        ['--text-2', 'text-2', 'Texte secondaire'],
                        ['--text-3', 'text-3', 'Texte tertiaire (≥ 4,5:1)'],
                        ['--success', 'success', 'Succès'],
                        ['--warning', 'warning', 'Avertissement'],
                        ['--danger', 'danger', 'Erreur'],
                    ] as [$token, $name, $usage])
                        <div class="rounded-md border border-line p-2">
                            <span class="block h-10 rounded-sm border border-line" style="background: var({{ $token }})"></span>
                            <p class="mt-2 font-mono text-[11.5px] text-ink">{{ $token }}</p>
                            <p class="text-[11.5px] text-ink-3">{{ $usage }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.card title="Typographie" variant="panel">
                    <div class="space-y-3">
                        <p class="text-page-title text-ink">Titre de page — 28/34</p>
                        <p class="text-section-title text-ink">Titre de section — 17/24</p>
                        <p class="text-sm text-ink">Corps de texte — 14/22, la taille de lecture par défaut.</p>
                        <p class="text-[13px] text-ink-2">Texte secondaire — 13/20 pour les descriptions.</p>
                        <p class="text-caption text-ink-3">Légende — 12/18, jamais utilisée seule pour une information critique.</p>
                        <p class="text-eyebrow text-ink-3">Intitulé de groupe</p>
                        <p class="font-mono text-sm text-ink">AGB-2026-00123 · IM-4471-LB</p>
                    </div>
                </x-ui.card>

                <x-ui.card title="Rayons, élévations et densité" variant="panel">
                    <div class="flex flex-wrap gap-3">
                        @foreach (['--r-sm' => '10', '--r-md' => '14', '--r-lg' => '20', '--r-pill' => '∞'] as $radius => $px)
                            <div class="grid size-20 place-items-center border border-line bg-hover text-[11px] text-ink-2" style="border-radius: var({{ $radius }})">{{ $px }} px</div>
                        @endforeach
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="material-panel p-3 text-[12.5px] text-ink">material-panel</div>
                        <div class="material-solid p-3 text-[12.5px] text-ink">material-solid</div>
                        <div class="material-overlay p-3 text-[12.5px] text-ink">material-overlay</div>
                        <div class="material-glass p-3 text-[12.5px] text-ink">material-glass</div>
                    </div>
                    <p class="mt-3 text-[12px] text-ink-3">Hauteur de contrôle : <span class="font-mono">var(--control-h)</span> · hauteur de ligne : <span class="font-mono">var(--row-h)</span>.</p>
                </x-ui.card>
            </div>
        </div>
    @endif

    @if ($section === 'primitives')
        <div class="space-y-4">
            <x-ui.card title="Boutons" description="Variantes, tailles et états." variant="panel">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button>Principal</x-ui.button>
                        <x-ui.button variant="secondary">Secondaire</x-ui.button>
                        <x-ui.button variant="ghost">Fantôme</x-ui.button>
                        <x-ui.button variant="danger">Danger</x-ui.button>
                        <x-ui.button variant="link">Lien</x-ui.button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button size="sm" icon="plus">Petit</x-ui.button>
                        <x-ui.button icon="search">Moyen</x-ui.button>
                        <x-ui.button size="lg" icon="check">Grand</x-ui.button>
                        <x-ui.button icon="pencil" label="Modifier" />
                        <x-ui.button icon="trash-2" variant="ghost" label="Supprimer" />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button disabled>Désactivé</x-ui.button>
                        <x-ui.button loading>Chargement</x-ui.button>
                        <x-ui.button variant="secondary" icon-right="chevron-right">Avec icône à droite</x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Badges, statuts et pastilles" variant="panel">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['neutral', 'accent', 'success', 'warning', 'danger', 'info'] as $tone)
                        <x-ui.badge :tone="$tone">{{ ucfirst($tone) }}</x-ui.badge>
                    @endforeach
                    <x-ui.badge tone="success" icon="check">Payé</x-ui.badge>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-ui.status :value="\App\Domain\Students\Enums\LifecycleStage::Enrollment" />
                    <x-ui.status value="pending" :enum="\App\Domain\Documents\Enums\DocumentReviewStatus::class" />
                    <x-ui.status :value="'approved'" :enum="\App\Domain\Documents\Enums\DocumentReviewStatus::class" dot />
                    <x-ui.status :value="'rejected'" :enum="\App\Domain\Documents\Enums\DocumentReviewStatus::class" compact />
                    <x-ui.status label-override="Sans mapping" />
                </div>
            </x-ui.card>

            <div class="grid gap-4 lg:grid-cols-3">
                <x-ui.card title="Avatars et groupes" variant="panel">
                    <div class="flex items-center gap-2">
                        <x-ui.avatar name="Awa Nguema" size="xs" />
                        <x-ui.avatar name="Jean-Marc Obame" size="sm" />
                        <x-ui.avatar name="Sylvie Mba" size="md" />
                        <x-ui.avatar name="Paul Mba" size="lg" />
                    </div>
                    <div class="mt-4"><x-ui.avatar-group :names="['Awa Nguema', 'Sylvie Mba', 'Jean-Marc Obame', 'Paul Mba']" :max="3" /></div>
                    <div class="mt-4 text-[12.5px] text-ink-3">Initiales calculées, couleur stable par nom.</div>
                </x-ui.card>

                <x-ui.card title="Progression et monnaie" variant="panel">
                    <div class="space-y-3">
                        <x-ui.progress :value="72" label="Dossier complété" />
                        <x-ui.progress :value="100" label="Heures pratiques" />
                        <x-ui.money :amount="29000" />
                        <x-ui.money :amount="1250000" class="text-lg font-bold" />
                        <x-ui.date :value="now()" format="long" />
                        <x-ui.date :value="now()->subDays(2)" relative />
                    </div>
                </x-ui.card>

                <x-ui.card title="Divers" variant="panel">
                    <div class="flex flex-wrap items-center gap-2 text-[13px] text-ink-2">
                        <x-ui.kbd keys="K" />
                        <x-ui.phone value="+241 06 00 00 00" />
                        <x-ui.tooltip text="Aide contextuelle"><x-ui.button size="sm" variant="ghost" icon="help-circle" label="Aide" /></x-ui.tooltip>
                    </div>
                    <x-ui.separator class="my-3" />
                    <x-ui.skeleton :lines="1" class="w-2/3" />
                    <x-ui.skeleton :lines="1" class="mt-2 w-1/2" />
                </x-ui.card>
            </div>
        </div>
    @endif

    @if ($section === 'formulaires')
        <x-ui.card title="Contrôles de formulaire" description="Chaque contrôle gère repos, focus, erreur et désactivation." variant="panel">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="demo-name" label="Nom complet" hint="Tel qu’il figurera sur les documents." required>
                    <x-form.input name="demo-name" placeholder="Ex. Awa Nguema" icon="user" />
                </x-form.field>
                <x-form.field name="demo-amount" label="Montant" optional>
                    <x-form.input name="demo-amount" type="number" suffix="FCFA" />
                </x-form.field>
                <x-form.field name="demo-select" label="Moniteur">
                    <x-form.select name="demo-select" :options="['1' => 'Awa Nguema', '2' => 'Jean-Marc Obame']" placeholder="Choisir un moniteur" />
                </x-form.field>
                <x-form.field name="demo-date" label="Date de séance">
                    <x-form.input name="demo-date" type="date" />
                </x-form.field>
                <x-form.field name="demo-notes" label="Notes internes" hint="Visible par l’équipe uniquement." class="sm:col-span-2">
                    <x-form.textarea name="demo-notes" rows="3" placeholder="Contexte, remarques…" />
                </x-form.field>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="space-y-3">
                    <x-form.checkbox name="demo-cb" label="Envoyer un rappel par e-mail" checked />
                    <x-form.radio name="demo-radio" value="a" label="Paiement espèces" checked />
                    <x-form.radio name="demo-radio" value="b" label="Airtel Money" />
                </div>
                <div class="space-y-3">
                    <x-form.switch name="demo-switch" label="Activer la boutique" checked />
                    <x-form.radio-card name="demo-card" value="std" title="Formule standard" description="20 séances de conduite." :checked="true" />
                    <x-form.radio-card name="demo-card" value="pro" title="Formule intensive" description="40 séances + examen blanc." />
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <x-form.field name="demo-combo" label="Recherche d’élève" hint="Frappe pour filtrer la liste.">
                    <x-form.combobox name="demo-combo" :options="['Awa Nguema', 'Sylvie Mba', 'Paul Mba']" />
                </x-form.field>
                <x-form.field name="demo-file" label="Pièce justificative" hint="PDF ou image, 5 Mo maximum.">
                    <x-form.file-drop name="demo-file" />
                </x-form.field>
                <x-form.money name="demo-money" label="Versement" />
                <x-form.stepper :steps="['Identité', 'Permis', 'Pièces', 'Validation']" :current="2" />
                <x-form.otp />
            </div>

            <div class="mt-5 space-y-3">
                <x-ui.alert tone="warning" title="Validation partielle">Deux pièces restent à vérifier avant l’inscription définitive.</x-ui.alert>
                <x-form.field name="demo-error" label="Champ en erreur" error="Ce champ est obligatoire.">
                    <x-form.input name="demo-error" :invalid="true" />
                </x-form.field>
            </div>

            <div class="mt-5">
                <p class="text-[12.5px] font-semibold text-ink">Barre d’actions collante</p>
                <x-form.actions>
                    <x-ui.button variant="ghost">Annuler</x-ui.button>
                    <x-ui.button icon="check">Enregistrer</x-ui.button>
                </x-form.actions>
            </div>
        </x-ui.card>
    @endif

    @if ($section === 'donnees')
        <div class="space-y-4">
            <x-ui.card title="Tableau" description="En-têtes collants, lignes 52 px (40 px en compact), cellules étiquetées sur mobile." variant="panel" padding="p-0">
                <div class="p-4">
                    <x-data.filter-bar
                        :action="route('design-system.index', ['section' => 'donnees'])"
                        search=""
                        placeholder="Rechercher un élève…"
                        :result-count="3"
                        :chips="[
                            ['label' => 'Étape', 'value' => 'Conduite', 'url' => '#'],
                            ['label' => 'Moniteur', 'value' => 'Awa Nguema', 'url' => '#'],
                        ]"
                        clear-url="#"
                    />
                </div>
                <x-data.table caption="Exemple de tableau" class="rounded-none border-0">
                    <thead>
                        <tr>
                            <th scope="col">Élève</th>
                            <th scope="col">Étape</th>
                            <th scope="col" class="text-right">Solde</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demo as $row)
                            <tr>
                                <td data-label="Élève">
                                    <div class="flex items-center gap-2">
                                        <x-ui.avatar :name="$row['name']" size="sm" />
                                        <span class="font-medium text-ink">{{ $row['name'] }}</span>
                                    </div>
                                </td>
                                <td data-label="Étape"><x-ui.status :value="$row['stage']" :enum="\App\Domain\Students\Enums\LifecycleStage::class" /></td>
                                <td data-label="Solde" class="text-right"><x-ui.money :amount="$row['due']" /></td>
                                <td data-label="Actions" class="text-right"><x-ui.button size="sm" variant="ghost" icon="pencil" label="Modifier" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data.table>
                <div class="p-4"><x-ui.pagination :paginator="new \Illuminate\Pagination\LengthAwarePaginator($demo, 3, 10)" /></div>
            </x-ui.card>

            <div class="grid gap-4 lg:grid-cols-4">
                <x-data.kpi label="Chiffre d’affaires du mois" :value="'1 250 000 FCFA'" delta="+12 %" delta-label="vs mois dernier" trend="up" icon="wallet" :spark="[4, 6, 5, 8, 7, 9, 12]" />
                <x-data.kpi label="Élèves actifs" :value="128" delta="-3" delta-label="ce mois" trend="down" icon="users" />
                <x-data.kpi label="Dossiers à valider" :value="7" icon="clipboard-check" :href="route('design-system.index', ['section' => 'donnees'])" hint="Mis à jour à l’instant" />
                <x-data.kpi label="Séances du jour" value="9" icon="calendar" />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-data.chart-card title="Inscriptions" description="6 derniers mois">
                    <x-data.bar-chart
                        :series="[
                            ['label' => 'Mai', 'value' => 9],
                            ['label' => 'Juin', 'value' => 12],
                            ['label' => 'Juil', 'value' => 8],
                            ['label' => 'Août', 'value' => 14],
                            ['label' => 'Sept', 'value' => 18],
                            ['label' => 'Oct', 'value' => 11],
                        ]"
                        label="Inscriptions par mois"
                    />
                </x-data.chart-card>
                <x-ui.card title="Activité récente" variant="solid">
                    <x-data.timeline :items="[
                        ['title' => 'Dossier validé', 'at' => now()->subHours(2), 'tone' => 'success', 'icon' => 'circle-check', 'description' => 'Toutes les pièces ont été approuvées.'],
                        ['title' => 'Paiement reçu', 'at' => now()->subDay(), 'tone' => 'accent', 'icon' => 'wallet', 'description' => 'Versement de 45 000 FCFA — espèces.', 'actor' => 'Awa Nguema'],
                        ['title' => 'Séance annulée', 'at' => now()->subDays(10), 'tone' => 'danger', 'icon' => 'ban', 'description' => 'Véhicule immobilisé.'],
                    ]" />
                </x-ui.card>
            </div>

            <x-ui.card title="États vides et erreurs" variant="panel" padding="p-0">
                <div class="grid gap-2 sm:grid-cols-2">
                    <x-ui.empty-state icon="inbox" title="Aucun résultat" description="Aucun élève ne correspond à ces filtres.">
                        <x-slot:actions><x-ui.button variant="secondary" icon="refresh-cw">Réinitialiser</x-ui.button></x-slot:actions>
                    </x-ui.empty-state>
                    <x-ui.error-state title="Chargement impossible" description="La connexion au serveur a échoué.">
                        <x-slot:actions><x-ui.button variant="secondary" icon="refresh-cw">Réessayer</x-ui.button></x-slot:actions>
                    </x-ui.error-state>
                </div>
            </x-ui.card>
        </div>
    @endif

    @if ($section === 'domaine')
        <div class="space-y-4">
            <x-ui.card title="Parcours élève" description="Cinq phases, quinze étapes, retours autorisés signalés." variant="panel">
                <x-domain.stage-stepper :stage="\App\Domain\Students\Enums\LifecycleStage::DossierSetup" />
            </x-ui.card>
            <div class="grid gap-4 lg:grid-cols-3">
                <x-ui.card title="Séance" variant="panel">
                    <x-domain.session-card :session="$demoSession" />
                </x-ui.card>
                <x-ui.card title="Pièces du dossier" variant="panel">
                    <x-domain.doc-checklist :items="[
                        ['label' => 'Pièce d’identité', 'status' => 'approved', 'description' => 'CNI recto-verso.'],
                        ['label' => 'Justificatif de domicile', 'status' => 'pending'],
                        ['label' => 'Photo', 'status' => 'rejected', 'reason' => 'Photo floue, à reprendre.'],
                    ]" />
                </x-ui.card>
                <x-ui.card title="Flotte et stock" variant="panel">
                    <x-domain.expiry-chip label="Assurance" :date="now()->addDays(9)" />
                    <x-domain.expiry-chip label="Visite technique" :date="now()->addDays(45)" class="mt-2" />
                    <x-domain.stock-bar :quantity="4" :threshold="10" class="mt-4" />
                    <x-domain.invoice-summary :invoice="$demoInvoice" class="mt-4" />
                </x-ui.card>
            </div>
            <x-ui.card title="Véhicule" variant="panel">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-domain.vehicle-card :vehicle="$demoVehicle" :expiring="true" />
                </div>
            </x-ui.card>
        </div>
    @endif

    @if ($section === 'overlays')
        <x-ui.card title="Surcouches" description="Dialogues, tiroirs, menus, popovers et notifications." variant="panel">
            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="secondary" x-on:click="$dispatch('open-dialog', { name: 'ds-dialog' })">Ouvrir un dialogue</x-ui.button>
                <x-ui.button variant="secondary" x-on:click="$dispatch('open-drawer', { name: 'ds-drawer' })">Ouvrir un tiroir</x-ui.button>
                <x-ui.button variant="secondary" x-data x-on:click="$store.toasts.push({ title: 'Séance enregistrée', message: 'La séance du 12/10 a été créée.', tone: 'success' })">Empiler un toast</x-ui.button>
                <x-ui.button variant="secondary" x-data x-on:click="window.dispatchEvent(new CustomEvent('palette:open'))">Ouvrir la palette</x-ui.button>
            </div>

            <x-ui.dialog name="ds-dialog" title="Confirmer la validation" description="Cette action est irréversible.">
                <p class="text-sm text-ink-2">Le dossier de l’élève sera marqué comme complet et son parcours avancera à l’étape « conduite ».</p>
                <div class="mt-4 flex justify-end gap-2">
                    <x-ui.button variant="ghost" x-on:click="$dispatch('close-dialog', { name: 'ds-dialog' })">Annuler</x-ui.button>
                    <x-ui.button icon="check" x-on:click="$dispatch('close-dialog', { name: 'ds-dialog' })">Valider</x-ui.button>
                </div>
            </x-ui.dialog>

            <x-ui.drawer name="ds-drawer" title="Détail de la séance">
                <p class="text-sm text-ink-2">Panneau latéral : contexte, actions rapides, historique.</p>
            </x-ui.drawer>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-ui.card title="Popup de confirmation" variant="flat">
                    <x-ui.button variant="danger" size="sm" x-on:click="$dispatch('open-confirm', { name: 'ds-confirm' })">Annuler la séance</x-ui.button>
                    <x-ui.confirm
                        name="ds-confirm"
                        title="Annuler cette séance ?"
                        consequence="L’élève et le moniteur seront notifiés par e-mail. La séance restera visible dans l’historique."
                        confirm-label="Annuler la séance"
                        :action="route('design-system.index', ['section' => 'overlays'])"
                        method="POST"
                    />
                </x-ui.card>
                <x-ui.card title="Popover et menus" variant="flat">
                    <div class="flex flex-wrap gap-2">
                        <x-ui.popover label="Filtrer">
                            <x-slot:trigger>
                                <span class="inline-flex size-9 items-center justify-center"><x-ui.icon name="filter" class="w-4 h-4" /></span>
                            </x-slot:trigger>
                            <p class="text-[13px] text-ink-2">Contenu du popover : filtres rapides, dates, moniteur.</p>
                        </x-ui.popover>
                        <x-ui.dropdown>
                            <x-slot:trigger>
                                <x-ui.button variant="secondary" size="sm" icon-right="chevron-down">Actions</x-ui.button>
                            </x-slot:trigger>
                            <x-ui.dropdown-item icon="eye" :href="route('design-system.index', ['section' => 'overlays'])">Consulter</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2">Supprimer</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    </div>
                </x-ui.card>
            </div>
        </x-ui.card>
    @endif

    @if ($section === 'etats')
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Alertes" variant="panel">
                <div class="space-y-2">
                    <x-ui.alert tone="info" title="Information">La synchronisation des préférences est active.</x-ui.alert>
                    <x-ui.alert tone="success" title="Succès">Les modifications ont été enregistrées.</x-ui.alert>
                    <x-ui.alert tone="warning" title="Attention">Trois pièces arrivent à expiration cette semaine.</x-ui.alert>
                    <x-ui.alert tone="danger" title="Erreur">Le paiement n’a pas pu être annulé.</x-ui.alert>
                    <x-ui.alert tone="accent" title="Astuce" dismissible>Utilisez ⌘K pour ouvrir la palette de commandes.</x-ui.alert>
                </div>
            </x-ui.card>

            <x-ui.card title="Chargement et squelettes" variant="panel">
                <div class="space-y-3">
                    <x-ui.skeleton class="h-4 w-1/3" />
                    <x-ui.skeleton class="h-24 w-full" />
                    <x-ui.skeleton class="h-4 w-2/3" />
                    <div class="flex items-center gap-2 text-[13px] text-ink-2"><x-ui.spinner class="w-4 h-4" /> Chargement des séances…</div>
                </div>
            </x-ui.card>

            <x-ui.card title="États d’erreur et d’attente" variant="panel" padding="p-0">
                <x-ui.empty-state icon="calendar" title="Aucune séance planifiée" description="Planifiez une première séance pour démarrer le parcours pédagogique.">
                    <x-slot:actions><x-ui.button icon="plus">Planifier</x-ui.button></x-slot:actions>
                </x-ui.empty-state>
            </x-ui.card>

            <x-ui.card title="Focus et accessibilité" variant="panel">
                <ul class="space-y-2 text-[13px] text-ink-2">
                    <li class="flex gap-2"><x-ui.icon name="check" class="w-4 h-4 text-success" /> Cible tactile minimale 44 px (36 px en compact).</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="w-4 h-4 text-success" /> Anneau de focus visible sur tous les contrôles.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="w-4 h-4 text-success" /> Statut toujours doublé d’un texte, jamais la couleur seule.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="w-4 h-4 text-success" /> Contrastes vérifiés par <span class="font-mono text-[12px]">npm run qa:contrast</span>.</li>
                </ul>
            </x-ui.card>
        </div>
    @endif
</x-app-layout>
