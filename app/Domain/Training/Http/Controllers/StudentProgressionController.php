<?php

namespace App\Domain\Training\Http\Controllers;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Read-only mirror of EvaluationController::show() for the eleve themselves -
 * the student is resolved from Auth::id(), never a route parameter, so
 * there's no id to forge and no policy check is needed.
 */
class StudentProgressionController extends Controller
{
    public function __invoke(): View
    {
        $student = Student::query()->where('user_id', Auth::id())->first();

        $skills = Skill::query()->orderBy('category')->orderBy('position')->get();
        $progress = $student
            ? SkillProgress::query()->where('student_id', $student->id)->get()->keyBy('skill_id')
            : collect();

        $levelPercent = ['not_started' => 0, 'in_progress' => 50, 'acquired' => 100];
        $overallPercent = $skills->isEmpty()
            ? 0
            : (int) round($skills->avg(fn (Skill $skill) => $levelPercent[$progress->get($skill->id)?->level?->value ?? 'not_started']));

        return view('eleve.progression', [
            'student' => $student,
            'skillsByCategory' => $skills->groupBy('category'),
            'progress' => $progress,
            'overallPercent' => $overallPercent,
        ]);
    }
}
