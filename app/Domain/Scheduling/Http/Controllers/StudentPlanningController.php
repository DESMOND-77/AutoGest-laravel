<?php

namespace App\Domain\Scheduling\Http\Controllers;

use App\Domain\Scheduling\Repositories\LessonSessionRepositoryInterface;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentPlanningController extends Controller
{
    public function __construct(
        private readonly LessonSessionRepositoryInterface $sessions,
    ) {}

    public function __invoke(Request $request): View
    {
        $student = Student::query()->where('user_id', Auth::id())->first();

        $week = $request->query('week')
            ? Carbon::parse($request->query('week'))->startOfWeek()
            : now()->startOfWeek();

        $sessions = $student
            ? $this->sessions->forStudentBetween($student->id, $week->toDateString(), $week->copy()->endOfWeek()->toDateString())
            : collect();

        return view('eleve.planning', [
            'student' => $student,
            'sessions' => $sessions,
            'week' => $week,
        ]);
    }
}
