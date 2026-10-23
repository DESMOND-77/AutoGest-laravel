<x-app-layout :title="'Entraînement au code'">
    <x-layout.page-header
        title="Entraînement au code"
        description="20 questions tirées au hasard. Votre score est enregistré et partagé avec votre moniteur."
        :breadcrumbs="[['label' => 'Accueil', 'href' => route('eleve.dashboard')], ['label' => 'Entraînement au code']]"
    />

    <div
        class="mx-auto max-w-2xl"
        x-data="{
            state: 'intro',
            loading: false,
            error: null,
            questions: [],
            answers: {},
            index: 0,
            score: null,
            total: null,
            attemptId: null,
            history: [],
            get current() { return this.questions[this.index] ?? null; },
            get answeredCount() { return Object.keys(this.answers).length; },
            get progress() { return this.questions.length ? Math.round((this.index / this.questions.length) * 100) : 0; },
            async start() {
                this.loading = true;
                this.error = null;
                this.state = 'playing';
                this.questions = [];
                this.answers = {};
                this.index = 0;
                try {
                    const res = await fetch(@js(route('quiz.index')), { headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error('Chargement impossible');
                    const payload = await res.json();
                    this.questions = payload.data ?? payload;
                    if (!this.questions.length) {
                        this.state = 'intro';
                        this.error = 'Aucune question n’est disponible pour le moment. Votre auto-école doit d’abord alimenter la banque de questions.';
                    }
                } catch (e) {
                    this.state = 'intro';
                    this.error = 'Impossible de charger les questions. Vérifiez votre connexion puis réessayez.';
                } finally {
                    this.loading = false;
                }
            },
            choose(question, option) {
                this.answers[question.id] = option.id;
                if (this.index < this.questions.length - 1) {
                    this.index++;
                }
            },
            previous() { if (this.index > 0) this.index--; },
            async submit() {
                this.loading = true;
                this.error = null;
                try {
                    const token = document.querySelector('meta[name=csrf-token]')?.content;
                    const res = await fetch(@js(route('quiz.store')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ answers: this.answers }),
                    });
                    if (!res.ok) throw new Error('Envoi refusé');
                    const data = await res.json();
                    this.score = data.score;
                    this.total = data.total_questions;
                    this.attemptId = data.attempt_id;
                    this.state = 'done';
                    this.loadHistory();
                    window.Alpine?.store('toasts')?.push('Score enregistré : ' + data.score + '/' + data.total_questions, 'success');
                } catch (e) {
                    this.error = 'Le score n’a pas pu être enregistré. Réessayez : vos réponses restent affichées.';
                } finally {
                    this.loading = false;
                }
            },
            async loadHistory() {
                try {
                    const res = await fetch(@js(route('quiz.results')), { headers: { Accept: 'application/json' } });
                    if (res.ok) this.history = await res.json();
                } catch (e) {}
            },
        }"
        x-init="loadHistory()"
    >
        {{-- Erreur --}}
        <template x-if="error">
            <div class="mb-4">
                <div class="rounded-md bg-danger-soft p-3 text-[13px] font-medium text-danger" role="alert" x-text="error"></div>
            </div>
        </template>

        {{-- Écran d'accueil --}}
        <div x-show="state === 'intro'" class="space-y-4">
            <x-ui.card variant="panel">
                <h2 class="text-section-title text-ink">Comment ça marche</h2>
                <ol class="mt-3 space-y-2 text-[13.5px] text-ink-2">
                    <li class="flex gap-2"><span class="font-semibold text-ink">1.</span> 20 questions à choix unique, tirées au hasard.</li>
                    <li class="flex gap-2"><span class="font-semibold text-ink">2.</span> Une seule réponse par question ; vous pouvez revenir en arrière.</li>
                    <li class="flex gap-2"><span class="font-semibold text-ink">3.</span> Le score est calculé côté serveur et visible par votre moniteur.</li>
                </ol>
                <x-ui.button class="mt-4 w-full" size="lg" icon="play" x-on:click="start()" x-bind:disabled="loading">
                    <span x-text="loading ? 'Chargement…' : 'Commencer la série'"></span>
                </x-ui.button>
            </x-ui.card>

            <x-ui.card x-show="history.length > 0" title="Mes dernières séries" variant="solid" padding="p-0">
                <ul class="divide-y divide-line">
                    <template x-for="attempt in history.slice(0, 5)" :key="attempt.id">
                        <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                            <span class="text-[13px] text-ink-2" x-text="new Date(attempt.completed_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })"></span>
                            <span class="text-[13px] font-semibold text-ink" x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                        </li>
                    </template>
                </ul>
            </x-ui.card>
        </div>

        {{-- Série en cours --}}
        <div x-show="state === 'playing'" x-cloak class="space-y-4">
            <div class="material-solid p-4">
                <div class="flex items-center justify-between gap-2 text-[12.5px] text-ink-3">
                    <span x-text="'Question ' + (index + 1) + ' / ' + questions.length"></span>
                    <span x-text="answeredCount + ' réponse(s)'"></span>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--surface-inset)]">
                    <div class="h-full rounded-full bg-accent transition-[width] duration-300" :style="'width:' + progress + '%'"></div>
                </div>

                <template x-if="current">
                    <div class="mt-4">
                        <h2 class="text-[16px] font-semibold leading-snug text-ink" x-text="current.prompt"></h2>
                        <p class="mt-1 text-[11.5px] uppercase tracking-wide text-ink-3" x-text="current.category || ''"></p>

                        <fieldset class="mt-4 space-y-2">
                            <legend class="sr-only">Choisissez une réponse</legend>
                            <template x-for="option in current.options" :key="option.id">
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-md border p-3 text-[13.5px] transition-colors"
                                    :class="answers[current.id] === option.id ? 'border-accent bg-accent-soft text-ink' : 'border-line hover:bg-hover text-ink-2'"
                                >
                                    <input type="radio" class="mt-0.5 size-4 accent-[var(--accent)]" :name="'q-' + current.id" :checked="answers[current.id] === option.id" x-on:change="choose(current, option)">
                                    <span x-text="option.text"></span>
                                </label>
                            </template>
                        </fieldset>
                    </div>
                </template>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                    <x-ui.button type="button" variant="ghost" icon="chevron-left" x-on:click="previous()" x-bind:disabled="index === 0">Précédente</x-ui.button>
                    <div class="flex items-center gap-2">
                        <x-ui.button type="button" variant="secondary" x-show="index < questions.length - 1"
                            x-on:click="index = Math.min(index + 1, questions.length - 1)">Question suivante</x-ui.button>
                        <x-ui.button type="button" icon="check" x-show="index === questions.length - 1"
                            x-on:click="submit()" x-bind:disabled="loading || answeredCount < questions.length">
                            <span x-text="loading ? 'Envoi…' : 'Terminer'"></span>
                        </x-ui.button>
                    </div>
                </div>

                <p class="mt-2 text-[11.5px] text-ink-3" x-show="answeredCount < questions.length">
                    Répondez à toutes les questions pour terminer la série.
                </p>
            </div>
        </div>

        {{-- Résultat --}}
        <div x-show="state === 'done'" x-cloak class="space-y-4">
            <x-ui.card variant="panel">
                <p class="text-eyebrow">Série terminée</p>
                <p class="mt-1 text-3xl font-bold tabular-nums text-ink"><span x-text="score"></span> <span class="text-lg font-medium text-ink-3">/ <span x-text="total"></span></span></p>
                <p class="mt-1 text-[13px] text-ink-2">Votre score a été enregistré. Il est visible par votre moniteur.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" icon="refresh-cw" x-on:click="start()">Nouvelle série</x-ui.button>
                    <x-ui.button :href="route('eleve.dashboard')" variant="secondary">Retour à l’accueil</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Historique" variant="solid" padding="p-0">
                <ul class="divide-y divide-line">
                    <template x-for="attempt in history" :key="attempt.id">
                        <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                            <span class="text-[13px] text-ink-2" x-text="new Date(attempt.completed_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })"></span>
                            <span class="text-[13px] font-semibold text-ink" x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                        </li>
                    </template>
                    <li x-show="history.length === 0" class="px-5 py-4 text-[13px] text-ink-3">Aucune série enregistrée pour le moment.</li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
