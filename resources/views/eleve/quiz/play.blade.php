<x-app-layout>
    <x-slot name="header">Entraînement au code</x-slot>

    <div class="py-6" x-data="quizApp()" x-init="init()">
        <div class="max-w-3xl mx-auto space-y-6">

            <div x-show="errorMessage" x-cloak class="rounded-ui-md p-4 text-sm bg-danger/10 text-danger" x-text="errorMessage"></div>

            {{-- START --}}
            <template x-if="phase === 'start'">
                <div class="space-y-6">
                    <div class="bg-surface shadow-soft rounded-ui-lg p-6 text-center space-y-4">
                        <h3 class="text-lg font-semibold text-content">Prêt à vous entraîner ?</h3>
                        <p class="text-sm text-content-secondary">
                            20 questions tirées au hasard, 45 secondes par question en moyenne.
                            Le score n'est calculé qu'une fois le test terminé.
                        </p>
                        <button
                            @click="start()"
                            class="inline-flex items-center rounded-ui-md bg-primary px-6 py-3 font-semibold text-sm text-primary-content shadow-soft-sm hover:shadow-soft-hover transition"
                        >
                            Commencer le test
                        </button>
                    </div>

                    <div class="bg-surface shadow-soft rounded-ui-lg p-6">
                        <div class="text-sm font-semibold text-content mb-3">Historique</div>
                        <template x-if="history.length === 0">
                            <p class="text-sm text-content-muted">Aucun test passé pour l'instant.</p>
                        </template>
                        <table class="w-full text-sm text-left" x-show="history.length > 0">
                            <thead class="text-content-muted">
                                <tr>
                                    <th class="py-1 font-medium">Date</th>
                                    <th class="py-1 font-medium">Score</th>
                                    <th class="py-1 font-medium">Résultat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <template x-for="entry in history" :key="entry.id">
                                    <tr>
                                        <td class="py-2 text-content-secondary" x-text="formatDate(entry.completed_at)"></td>
                                        <td class="py-2 text-content" x-text="entry.score + ' / ' + entry.total_questions"></td>
                                        <td class="py-2">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs"
                                                :class="passed(entry.score, entry.total_questions) ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                                                x-text="passed(entry.score, entry.total_questions) ? 'Réussi' : 'Insuffisant'"
                                            ></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            {{-- LOADING / SUBMITTING --}}
            <template x-if="phase === 'loading' || phase === 'submitting'">
                <div class="bg-surface shadow-soft rounded-ui-lg p-6 text-center text-content-muted">
                    <span x-text="phase === 'loading' ? 'Chargement des questions…' : 'Correction en cours…'"></span>
                </div>
            </template>

            {{-- PLAYING --}}
            <template x-if="phase === 'playing' && currentQuestion">
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm text-content-secondary">
                        <span x-text="'Question ' + (current + 1) + ' / ' + questions.length"></span>
                        <span class="font-mono" :class="secondsLeft <= 30 ? 'text-danger font-semibold' : ''" x-text="formattedTime"></span>
                    </div>

                    <div class="w-full bg-surface-inset rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all" :style="`width: ${progressPercent}%`"></div>
                    </div>

                    <div class="bg-surface shadow-soft rounded-ui-lg p-6 space-y-4">
                        <p class="text-base font-medium text-content" x-text="currentQuestion.prompt"></p>

                        <div class="space-y-2">
                            <template x-for="option in currentQuestion.options" :key="option.id">
                                <label
                                    class="flex items-center gap-3 p-3 rounded-ui-md cursor-pointer transition"
                                    :class="answers[currentQuestion.id] === option.id
                                        ? 'shadow-inset bg-surface-inset'
                                        : 'bg-surface-elevated hover:shadow-soft-sm'"
                                >
                                    <input
                                        type="radio"
                                        class="shrink-0 text-primary focus:ring-primary"
                                        :name="'question-' + currentQuestion.id"
                                        :checked="answers[currentQuestion.id] === option.id"
                                        @change="choose(option.id)"
                                    >
                                    <span class="text-sm text-content" x-text="option.text"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <x-secondary-button @click="previous()" x-bind:disabled="current === 0" class="inline-flex items-center gap-1">
                            <x-icon name="chevron-left" class="w-4 h-4" /> Précédent
                        </x-secondary-button>

                        <button
                            x-show="current < questions.length - 1"
                            @click="next()"
                            :disabled="!answers[currentQuestion.id]"
                            class="inline-flex items-center gap-1 rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition disabled:opacity-40 disabled:pointer-events-none"
                        >
                            Suivant <x-icon name="chevron-right" class="w-4 h-4" />
                        </button>

                        <button
                            x-show="current === questions.length - 1"
                            @click="submit()"
                            :disabled="answeredCount === 0"
                            class="rounded-ui-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-content shadow-soft-sm hover:shadow-soft-hover transition disabled:opacity-40 disabled:pointer-events-none"
                        >
                            Terminer le test
                        </button>
                    </div>
                </div>
            </template>

            {{-- RESULT / CORRECTION --}}
            <template x-if="phase === 'result' && correction">
                <div class="space-y-4">
                    <div class="bg-surface shadow-soft rounded-ui-lg p-6 text-center space-y-2">
                        <p class="text-3xl font-bold text-content" x-text="correction.score + ' / ' + correction.total_questions"></p>
                        <span
                            class="inline-block px-3 py-1 rounded-full text-sm"
                            :class="passed(correction.score, correction.total_questions) ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                            x-text="passed(correction.score, correction.total_questions) ? 'Réussi' : 'Insuffisant (70% requis)'"
                        ></span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(question, index) in correction.questions" :key="question.id">
                            <div class="bg-surface shadow-soft rounded-ui-lg p-4">
                                <p class="text-sm font-medium text-content mb-2" x-text="(index + 1) + '. ' + question.prompt"></p>
                                <div class="space-y-1">
                                    <template x-for="option in question.options" :key="option.id">
                                        <div
                                            class="text-sm px-3 py-2 rounded-ui-sm"
                                            :class="{
                                                'bg-success/10 text-success': option.is_correct,
                                                'bg-danger/10 text-danger': !option.is_correct && option.id === question.chosen_option_id,
                                                'text-content-muted': !option.is_correct && option.id !== question.chosen_option_id,
                                            }"
                                        >
                                            <span x-text="option.text"></span>
                                            <span x-show="option.id === question.chosen_option_id" class="text-xs ml-1">(votre réponse)</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="text-center">
                        <button
                            @click="restart()"
                            class="inline-flex items-center rounded-ui-md bg-primary px-6 py-3 font-semibold text-sm text-primary-content shadow-soft-sm hover:shadow-soft-hover transition"
                        >
                            Recommencer
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function quizApp() {
            const SECONDS_PER_QUESTION = 45;
            const PASS_THRESHOLD = 0.7;

            return {
                phase: 'start',
                questions: [],
                current: 0,
                answers: {},
                secondsLeft: 0,
                timer: null,
                attempt: null,
                correction: null,
                history: [],
                errorMessage: '',

                init() {
                    this.loadHistory();
                },

                async loadHistory() {
                    const response = await fetch('{{ route('quiz.results') }}', {
                        headers: { Accept: 'application/json' },
                    });
                    this.history = await response.json();
                },

                async start() {
                    this.errorMessage = '';
                    this.phase = 'loading';

                    const response = await fetch('{{ route('quiz.index') }}', {
                        headers: { Accept: 'application/json' },
                    });
                    // QuizQuestionResource::collection() wraps the array in
                    // Laravel's default {"data": [...]} envelope, unlike the
                    // plain response()->json(...) calls elsewhere in this
                    // controller - the other fetches in this file don't need
                    // this unwrap.
                    const payload = await response.json();
                    this.questions = payload.data;
                    this.current = 0;
                    this.answers = {};
                    this.secondsLeft = this.questions.length * SECONDS_PER_QUESTION;
                    this.phase = 'playing';
                    this.startTimer();
                },

                startTimer() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        this.secondsLeft--;

                        if (this.secondsLeft <= 0) {
                            clearInterval(this.timer);
                            this.submit();
                        }
                    }, 1000);
                },

                get currentQuestion() {
                    return this.questions[this.current] ?? null;
                },

                get answeredCount() {
                    return Object.keys(this.answers).length;
                },

                get progressPercent() {
                    return this.questions.length
                        ? Math.round(((this.current + 1) / this.questions.length) * 100)
                        : 0;
                },

                get formattedTime() {
                    const minutes = Math.max(0, Math.floor(this.secondsLeft / 60)).toString().padStart(2, '0');
                    const seconds = Math.max(0, this.secondsLeft % 60).toString().padStart(2, '0');

                    return `${minutes}:${seconds}`;
                },

                choose(optionId) {
                    this.answers[this.currentQuestion.id] = optionId;
                },

                next() {
                    if (this.current < this.questions.length - 1) {
                        this.current++;
                    }
                },

                previous() {
                    if (this.current > 0) {
                        this.current--;
                    }
                },

                async submit() {
                    clearInterval(this.timer);

                    if (this.answeredCount === 0) {
                        this.errorMessage = "Le temps est écoulé et aucune réponse n'a été donnée.";
                        this.phase = 'start';

                        return;
                    }

                    this.phase = 'submitting';

                    const response = await fetch('{{ route('quiz.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ answers: this.answers }),
                    });

                    if (!response.ok) {
                        this.errorMessage = "Impossible d'enregistrer votre test, réessayez.";
                        this.phase = 'playing';

                        return;
                    }

                    this.attempt = await response.json();

                    const correctionUrl = '{{ route('quiz.attempts.show', ['attempt' => '__ID__']) }}'
                        .replace('__ID__', this.attempt.attempt_id);
                    const correctionResponse = await fetch(correctionUrl, {
                        headers: { Accept: 'application/json' },
                    });
                    this.correction = await correctionResponse.json();

                    this.phase = 'result';
                    this.loadHistory();
                },

                restart() {
                    this.phase = 'start';
                    this.questions = [];
                    this.current = 0;
                    this.answers = {};
                    this.attempt = null;
                    this.correction = null;
                    this.errorMessage = '';
                },

                passed(score, total) {
                    return total > 0 && (score / total) >= PASS_THRESHOLD;
                },

                formatDate(value) {
                    if (! value) {
                        return '-';
                    }

                    return new Date(value).toLocaleDateString('fr-FR');
                },
            };
        }
    </script>
</x-app-layout>
