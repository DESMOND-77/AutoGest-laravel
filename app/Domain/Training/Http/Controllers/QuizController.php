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

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizGradingService $grading,
    ) {}

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

    private function attemptsFor(Student $student): JsonResponse
    {
        $attempts = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->latest('completed_at')
            ->get(['id', 'score', 'total_questions', 'completed_at']);

        return response()->json($attempts);
    }
}
