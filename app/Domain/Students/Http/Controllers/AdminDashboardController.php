<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalStudents' => Student::query()->count(),
            'formerStudents' => Student::query()
                ->where('lifecycle_stage', LifecycleStage::FormerStudent)
                ->count(),
        ]);
    }
}
