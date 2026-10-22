<?php

namespace App\Listeners;

use App\Domain\Notifications\Notifications\AlertNotification;
use App\Domain\Students\Events\StudentPublicRegistrationCompleted;
use App\Models\User;

class NotifyAdminsOnStudentPublicRegistration
{
    public function handle(StudentPublicRegistrationCompleted $event): void
    {
        $student = $event->student;

        $notification = new AlertNotification(
            title: 'Nouvelle demande d\'inscription',
            message: "{$student->fullName()} s'est inscrit(e) via votre lien public d'inscription.",
            link: route('students.show', $student, absolute: false),
        );

        User::role('admin')
            ->where('structure_id', $student->structure_id)
            ->get()
            ->each(fn (User $admin) => $admin->notify($notification));
    }
}
