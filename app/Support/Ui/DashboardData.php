<?php

namespace App\Support\Ui;

use App\Domain\Documents\Models\Document;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Données d'accueil des deux espaces dont la route est un `Route::view`
 * (aucun contrôleur à modifier) : le moniteur doit voir toute sa journée dès
 * l'ouverture, l'élève doit voir son parcours et ce qu'il lui reste à faire.
 */
class DashboardData
{
    /** @return array<string, mixed> */
    public static function forMonitor(User $user): array
    {
        $instructor = Instructor::query()->where('user_id', $user->id)->first();

        $sessions = $instructor
            ? LessonSession::query()
                ->where('instructor_id', $instructor->id)
                ->where('scheduled_date', now()->toDateString())
                ->with(['student', 'vehicle'])
                ->orderBy('starts_at')
                ->get()
            : collect();

        $next = $instructor
            ? LessonSession::query()
                ->where('instructor_id', $instructor->id)
                ->where('scheduled_date', '>=', now()->toDateString())
                ->where('starts_at', '>=', now()->format('H:i:s'))
                ->with(['student', 'vehicle'])
                ->orderBy('scheduled_date')
                ->orderBy('starts_at')
                ->first()
            : null;

        $students = $instructor
            ? Student::query()->where('instructor_id', $instructor->id)->count()
            : 0;

        $vehicles = $instructor
            ? LessonSession::query()
                ->where('instructor_id', $instructor->id)
                ->whereNotNull('vehicle_id')
                ->with('vehicle')
                ->get()
                ->pluck('vehicle')
                ->filter()
                ->unique('id')
            : collect();

        return [
            'instructor' => $instructor,
            'todaysSessions' => $sessions,
            'nextSession' => $next,
            'assignedStudentsCount' => $students,
            'assignedVehicles' => $vehicles,
            'unmarkedCount' => $sessions->filter(fn ($s) => $s->presence?->value === 'planned')->count(),
        ];
    }

    /** @return array<string, mixed> */
    public static function forStudent(User $user): array
    {
        $student = Student::query()->where('user_id', $user->id)->first();

        if (! $student) {
            return ['student' => null];
        }

        $upcoming = LessonSession::query()
            ->where('student_id', $student->id)
            ->where('scheduled_date', '>=', now()->toDateString())
            ->with(['instructor', 'vehicle'])
            ->orderBy('scheduled_date')
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->latest('issued_at')
            ->get()
            ->take(3);

        $total = (float) Invoice::query()->where('student_id', $student->id)->sum('amount_due');
        $paid = (float) Invoice::query()->where('student_id', $student->id)->sum('amount_paid');

        $documents = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->get();

        $skills = SkillProgress::query()
            ->where('student_id', $student->id)
            ->get();

        $lastAttempt = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->latest('completed_at')
            ->first();

        $nextExam = Exam::query()
            ->where('student_id', $student->id)
            ->where('exam_date', '>=', now()->toDateString())
            ->orderBy('exam_date')
            ->first();

        return [
            'student' => $student,
            'upcomingSessions' => $upcoming,
            'invoices' => $invoices,
            'totalDue' => $total,
            'totalPaid' => $paid,
            'balance' => max(0, $total - $paid),
            'documents' => $documents,
            'skillsAcquired' => $skills->filter(fn ($progress) => $progress->level?->value === 'acquired')->count(),
            'skillsTotal' => $skills->count(),
            'lastAttempt' => $lastAttempt,
            'nextExam' => $nextExam,
        ];
    }

    /** @return Collection<int, mixed> */
    public static function emptyCollection(): Collection
    {
        return collect();
    }
}
