<?php

namespace App\Domain\Scheduling\Http\Controllers;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Exceptions\SchedulingConflict;
use App\Domain\Scheduling\Http\Requests\StoreLessonSessionRequest;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Services\SchedulingService;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LessonSessionController extends Controller
{
    public function __construct(
        private readonly SchedulingService $scheduling,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LessonSession::class);

        $week = $request->query('week')
            ? Carbon::parse($request->query('week'))->startOfWeek()
            : now()->startOfWeek();

        $sessions = LessonSession::query()
            ->whereBetween('scheduled_date', [$week->toDateString(), $week->copy()->endOfWeek()->toDateString()])
            ->with(['student', 'instructor', 'vehicle'])
            ->orderBy('scheduled_date')->orderBy('starts_at')
            ->get();

        return view('scheduling.index', [
            'sessions' => $sessions,
            'week' => $week,
            'students' => Student::query()->orderBy('last_name')->get(),
            'instructors' => User::role('moniteur')->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('plate')->get(),
        ]);
    }

    public function store(StoreLessonSessionRequest $request): RedirectResponse
    {
        try {
            $this->scheduling->schedule($request->validated());
        } catch (SchedulingConflict $e) {
            return back()->withErrors(['starts_at' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Séance planifiée.');
    }

    public function destroy(LessonSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        $this->scheduling->markPresence($session, PresenceStatus::Cancelled);

        return back()->with('status', 'Séance annulée.');
    }

    public function markPresence(Request $request, LessonSession $session): RedirectResponse
    {
        $this->authorize('markPresence', $session);

        $status = PresenceStatus::from($request->validate([
            'presence' => ['required', 'string'],
        ])['presence']);

        $this->scheduling->markPresence($session, $status);

        return back()->with('status', 'Présence mise à jour.');
    }
}
