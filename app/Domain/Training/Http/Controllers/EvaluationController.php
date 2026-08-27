<?php

namespace App\Domain\Training\Http\Controllers;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Domain\Training\Services\EvaluationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluation,
    ) {}

    /**
     * The legacy moniteur/evaluation.php resolved $selEleve from a raw
     * ?eleve=<id> query param with no structure_id/moniteur_id check
     * (fixs.md #4). Here the target student is a route-bound model and every
     * access is gated by StudentPolicy::evaluate() before any progress is
     * read or written.
     */
    public function show(Student $student): View
    {
        $this->authorize('evaluate', $student);

        $skillsByCategory = Skill::query()->orderBy('category')->orderBy('position')->get()->groupBy('category');

        return view('training.evaluation.show', [
            'student' => $student,
            'skillsByCategory' => $skillsByCategory,
            'progress' => SkillProgress::query()->where('student_id', $student->id)->get()->keyBy('skill_id'),
        ]);
    }

    public function store(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('evaluate', $student);

        $data = $request->validate([
            'levels' => ['required', 'array'],
            'levels.*' => ['required', 'in:'.implode(',', array_column(SkillLevel::cases(), 'value'))],
        ]);

        $this->evaluation->record($student, $data['levels'], Auth::user());

        return back()->with('status', 'Évaluation enregistrée.');
    }
}
