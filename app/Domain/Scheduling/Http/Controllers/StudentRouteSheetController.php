<?php

namespace App\Domain\Scheduling\Http\Controllers;

use App\Domain\Scheduling\Services\StudentSessionSummaryService;
use App\Domain\Students\Models\Student;
use App\Domain\Training\Services\EvaluationService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Consolidated per-student view for a moniteur: session totals/presence/
 * driving hours + detailed history + skill-progress summary. Access is
 * gated by the existing StudentPolicy::view() (already restricts a
 * moniteur to their own assigned students) - no new policy needed.
 */
class StudentRouteSheetController extends Controller
{
    public function __construct(
        private readonly StudentSessionSummaryService $sessions,
        private readonly EvaluationService $evaluation,
    ) {}

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        return view('moniteur.feuille-route', [
            'student' => $student,
            'summary' => $this->sessions->summarize($student, Auth::user()),
            'skillSummary' => $this->evaluation->acquiredSummary($student),
        ]);
    }
}
