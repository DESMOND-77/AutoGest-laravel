<?php

namespace App\Domain\Training\Http\Controllers;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Http\Requests\StoreQuizAttemptRequest;
use App\Domain\Training\Http\Resources\QuizQuestionResource;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\QuizQuestion;
use App\Domain\Training\Services\QuizGradingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizGradingService $grading,
    ) {}

    /**
     * The page shell for the quiz flow (start screen, question-by-question
     * play, correction, history) — everything after this loads via fetch()
     * against index()/store()/results()/showAttempt() below. See §24 of the
     * project's audit roadmap: the grading backend already existed, only
     * the UI was missing.
     */
    public function play(): View
    {
        $this->authorize('create', QuizAttempt::class);

        return view('eleve.quiz.play');
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('create', QuizAttempt::class);

        $questions = QuizQuestion::query()->with('options')->inRandomOrder()->limit(20)->get();

        return QuizQuestionResource::collection($questions);
    }

    public function store(StoreQuizAttemptRequest $request): JsonResponse
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();

        $attempt = $this->grading->grade($student, $request->validated('answers'));

        return response()->json([
            'attempt_id' => $attempt->id,
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
        ], 201);
    }

    public function results(): JsonResponse
    {
        $this->authorize('viewAny', QuizAttempt::class);

        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();

        return $this->attemptsFor($student);
    }

    /**
     * Admin/moniteur view of another student's attempts — reuses
     * StudentPolicy::view() so a moniteur only sees their own assigned
     * students, exactly like every other student-scoped page.
     */
    public function studentResults(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return $this->attemptsFor($student);
    }

    /**
     * Correction detail for one already-completed attempt — unlike index()
     * (the live question set), this is allowed to reveal is_correct on
     * every option, since the attempt is already graded and locked. The
     * question order matches the order the student answered in, not a
     * fresh random draw.
     */
    public function showAttempt(QuizAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);

        $attempt->load('answers.question.options');

        return response()->json([
            'id' => $attempt->id,
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
            'completed_at' => $attempt->completed_at,
            'questions' => $attempt->answers->map(fn ($answer) => [
                'id' => $answer->question->id,
                'prompt' => $answer->question->prompt,
                'options' => $answer->question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'text' => $option->text,
                    'is_correct' => $option->is_correct,
                ]),
                'chosen_option_id' => $answer->option_id,
            ]),
        ]);
    }

    private function attemptsFor(Student $student): JsonResponse
    {
        $attempts = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->latest('completed_at')
            ->get(['id', 'score', 'total_questions', 'completed_at']);

        return response()->json($attempts);
    }
}
