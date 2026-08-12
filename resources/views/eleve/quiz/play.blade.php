<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Entraînement au code
        </h2>
    </x-slot>

    <div class="py-12" x-data="quizApp()" x-init="init()">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div x-show="errorMessage" x-cloak class="bg-red-100 text-red-800 text-sm rounded-md p-3" x-text="errorMessage"></div>

            {{-- START --}}
            <template x-if="phase === 'start'">
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-center space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Prêt à vous entraîner ?</h3>
                        <p class="text-sm text-gray-500">
                            20 questions tirées au hasard, 45 secondes par question en moyenne.
                            Le score n'est calculé qu'une fois le test terminé.
                        </p>
                        <button
                            @click="start()"
                            class="inline-flex items-center px-6 py-3 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-sm text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white"
                        >
                            Commencer le test
                        </button>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Historique</div>
                        <template x-if="history.length === 0">
                            <p class="text-sm text-gray-500">Aucun test passé pour l'instant.</p>
                        </template>
                        <table class="w-full text-sm text-left" x-show="history.length > 0">
                            <thead class="text-gray-500">
                                <tr>
                                    <th class="py-1">Date</th>
                                    <th class="py-1">Score</th>
                                    <th class="py-1">Résultat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <template x-for="entry in history" :key="entry.id">
                                    <tr>
                                        <td class="py-1" x-text="formatDate(entry.completed_at)"></td>
                                        <td class="py-1" x-text="entry.score + ' / ' + entry.total_questions"></td>
                                        <td class="py-1">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs"
                                                :class="passed(entry.score, entry.total_questions) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
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
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-center text-gray-500">
                    <span x-text="phase === 'loading' ? 'Chargement des questions…' : 'Correction en cours…'"></span>
                </div>
            </template>

            {{-- PLAYING --}}
            <template x-if="phase === 'playing' && currentQuestion">
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span x-text="'Question ' + (current + 1) + ' / ' + questions.length"></span>
                        <span class="font-mono" :class="secondsLeft <= 30 ? 'text-red-600 font-semibold' : ''" x-text="formattedTime"></span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gray-800 dark:bg-gray-200 h-2 rounded-full transition-all" :style="`width: ${progressPercent}%`"></div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                        <p class="text-base font-medium text-gray-900 dark:text-gray-100" x-text="currentQuestion.prompt"></p>

                        <div class="space-y-2">
                            <template x-for="option in currentQuestion.options" :key="option.id">
                                <label
                                    class="flex items-center gap-3 p-3 border rounded-md cursor-pointer"
                                    :class="answers[currentQuestion.id] === option.id
                                        ? 'border-gray-800 dark:border-gray-200 bg-gray-50 dark:bg-gray-700'
                                        : 'border-gray-200 dark:border-gray-700'"
                                >
                                    <input
                                        type="radio"
                                        class="shrink-0"
                                        :name="'question-' + currentQuestion.id"
                                        :checked="answers[currentQuestion.id] === option.id"
                                        @change="choose(option.id)"
                                    >
                                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="option.text"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button
                            @click="previous()"
                            :disabled="current === 0"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 disabled:opacity-40"
                        >
                            &larr; Précédent
                        </button>

                        <button
                            x-show="current < questions.length - 1"
                            @click="next()"
                            :disabled="!answers[currentQuestion.id]"
                            class="px-4 py-2 bg-gray-800 dark:bg-gray-200 rounded-md text-sm text-white dark:text-gray-800 disabled:opacity-40"
                        >
                            Suivant &rarr;
                        </button>

                        <button
                            x-show="current === questions.length - 1"
                            @click="submit()"
                            :disabled="answeredCount === 0"
                            class="px-4 py-2 bg-gray-800 dark:bg-gray-200 rounded-md text-sm text-white dark:text-gray-800 disabled:opacity-40"
                        >
                            Terminer le test
                        </button>
                    </div>
                </div>
            </template>

            {{-- RESULT / CORRECTION --}}
            <template x-if="phase === 'result' && correction">
                <div class="space-y-4">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-center space-y-2">
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100" x-text="correction.score + ' / ' + correction.total_questions"></p>
                        <span
                            class="inline-block px-3 py-1 rounded-full text-sm"
                            :class="passed(correction.score, correction.total_questions) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            x-text="passed(correction.score, correction.total_questions) ? 'Réussi' : 'Insuffisant (70% requis)'"
                        ></span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(question, index) in correction.questions" :key="question.id">
                            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2" x-text="(index + 1) + '. ' + question.prompt"></p>
                                <div class="space-y-1">
                                    <template x-for="option in question.options" :key="option.id">
                                        <div
                                            class="text-sm px-3 py-2 rounded-md"
                                            :class="{
                                                'bg-green-100 text-green-800': option.is_correct,
                                                'bg-red-100 text-red-800': !option.is_correct && option.id === question.chosen_option_id,
                                                'text-gray-500': !option.is_correct && option.id !== question.chosen_option_id,
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
                            class="inline-flex items-center px-6 py-3 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-sm text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white"
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
                    // controller — the other fetches in this file don't need
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
                        return '—';
                    }

                    return new Date(value).toLocaleDateString('fr-FR');
                },
            };
        }
    </script>
</x-app-layout>
