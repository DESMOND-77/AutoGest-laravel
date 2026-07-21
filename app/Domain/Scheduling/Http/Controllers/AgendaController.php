<?php

namespace App\Domain\Scheduling\Http\Controllers;

use App\Domain\Scheduling\Repositories\LessonSessionRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __construct(
        private readonly LessonSessionRepositoryInterface $sessions,
    ) {}

    public function __invoke(Request $request): View
    {
        $week = $request->query('week')
            ? Carbon::parse($request->query('week'))->startOfWeek()
            : now()->startOfWeek();

        $sessions = $this->sessions->forInstructorBetween(
            Auth::id(),
            $week->toDateString(),
            $week->copy()->endOfWeek()->toDateString(),
        );

        return view('moniteur.agenda', [
            'sessions' => $sessions,
            'week' => $week,
        ]);
    }
}
