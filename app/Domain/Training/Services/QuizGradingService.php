<?php

namespace App\Domain\Training\Services;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

/**
 * The only place a quiz attempt is ever scored. Correctness is always
 * resolved server-side from QuizOption::is_correct — the request only ever
 * supplies which option id the student picked, never whether it was right.
 * This closes the legacy Code Rousseau bug: the old quiz sent the correct
 * answers to the browser and revalidated the score against whatever the
 * client sent back, so a technically-minded student could fabricate a
 * perfect score.
 */
class QuizGradingService
{
    /**
     * @param  array<int, int>  $answers  [question_id => option_id]
     */
    public function grade(Student $student, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($student, $answers) {
            $questions = QuizQuestion::query()
                ->whereIn('id', array_keys($answers))
                ->where('structure_id', $student->structure_id)
                ->with('options')
                ->get()
                ->keyBy('id');

            $attempt = QuizAttempt::query()->create([
                'structure_id' => $student->structure_id,
                'student_id' => $student->id,
                'score' => 0,
                'total_questions' => count($questions),
                'completed_at' => now(),
            ]);

            $score = 0;

            foreach ($answers as $questionId => $optionId) {
                $question = $questions->get($questionId);

                if (! $question) {
                    continue;
                }

                $option = $question->options->firstWhere('id', $optionId);

                if (! $option) {
                    continue;
                }

                if ($option->is_correct) {
                    $score++;
                }

                $attempt->answers()->create([
                    'question_id' => $questionId,
                    'option_id' => $optionId,
                ]);
            }

            $attempt->update(['score' => $score]);

            return $attempt;
        });
    }
}
