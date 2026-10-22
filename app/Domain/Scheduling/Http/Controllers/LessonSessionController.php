<?php

namespace App\Domain\Scheduling\Http\Controllers;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Exceptions\SchedulingConflict;
use App\Domain\Scheduling\Http\Requests\StoreLessonSessionRequest;
use App\Domain\Scheduling\Http\Requests\UpdateLessonSessionRequest;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Services\SchedulingService;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CsvExporter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonSessionController extends Controller
{
    public function __construct(
        private readonly SchedulingService $scheduling,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LessonSession::class);

        $week = $this->weekFrom($request);
        $sessions = $this->filteredSessions($request, $week);

        return view('scheduling.index', [
            'sessions' => $sessions,
            'week' => $week,
            'filters' => $request->only(['instructor_id', 'vehicle_id', 'student_id']),
            'students' => Student::query()->orderBy('last_name')->get(),
            'instructors' => User::role('moniteur')->active()->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('plate')->get(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', LessonSession::class);

        $week = $this->weekFrom($request);
        $sessions = $this->filteredSessions($request, $week);

        $rows = $sessions->map(fn (LessonSession $session) => [
            $session->scheduled_date->format('d/m/Y'),
            substr($session->starts_at, 0, 5),
            substr($session->ends_at, 0, 5),
            $session->student->fullName(),
            $session->instructor->name,
            $session->vehicle->plate ?? '',
            $session->type->label(),
            $session->presence->label(),
        ]);

        return CsvExporter::stream(
            "planning-semaine-{$week->toDateString()}.csv",
            ['Date', 'Début', 'Fin', 'Élève', 'Moniteur', 'Véhicule', 'Type', 'Présence'],
            $rows,
        );
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

    public function update(UpdateLessonSessionRequest $request, LessonSession $session): RedirectResponse
    {
        try {
            $this->scheduling->reschedule($session, $request->validated());
        } catch (SchedulingConflict $e) {
            return back()->withErrors(['starts_at' => $e->getMessage()])->withInput()->with('editingSessionId', $session->id);
        }

        return back()->with('status', 'Séance mise à jour.');
    }

    public function duplicate(Request $request, LessonSession $session): RedirectResponse
    {
        $this->authorize('create', LessonSession::class);

        $data = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);

        try {
            $this->scheduling->schedule([
                'student_id' => $session->student_id,
                'instructor_id' => $session->instructor_id,
                'vehicle_id' => $session->vehicle_id,
                'type' => $session->type->value,
                'scheduled_date' => $data['scheduled_date'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ]);
        } catch (SchedulingConflict $e) {
            return back()->withErrors(['duplicate' => $e->getMessage()])->with('duplicatingSessionId', $session->id);
        }

        return back()->with('status', 'Séance dupliquée.');
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

    private function weekFrom(Request $request): Carbon
    {
        return $request->query('week')
            ? Carbon::parse($request->query('week'))->startOfWeek()
            : now()->startOfWeek();
    }

    /**
     * @return Collection<int, LessonSession>
     */
    private function filteredSessions(Request $request, Carbon $week): Collection
    {
        $filters = $request->only(['instructor_id', 'vehicle_id', 'student_id']);

        return LessonSession::query()
            ->whereBetween('scheduled_date', [$week->toDateString(), $week->copy()->endOfWeek()->toDateString()])
            ->when($filters['instructor_id'] ?? null, fn ($query, $value) => $query->where('instructor_id', $value))
            ->when($filters['vehicle_id'] ?? null, fn ($query, $value) => $query->where('vehicle_id', $value))
            ->when($filters['student_id'] ?? null, fn ($query, $value) => $query->where('student_id', $value))
            ->with(['student', 'instructor', 'vehicle'])
            ->orderBy('scheduled_date')->orderBy('starts_at')
            ->get();
    }
}
