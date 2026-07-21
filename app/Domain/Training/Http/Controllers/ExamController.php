<?php

namespace App\Domain\Training\Http\Controllers;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Http\Requests\StoreExamRequest;
use App\Domain\Training\Http\Requests\UpdateExamResultRequest;
use App\Domain\Training\Models\Exam;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Exam::class);

        return view('training.exams.index', [
            'exams' => Exam::query()->with('student')->latest('exam_date')->paginate(20),
            'students' => Student::query()->orderBy('last_name')->get(),
        ]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        Exam::query()->create($request->validated());

        return back()->with('status', 'Examen enregistré.');
    }

    public function update(UpdateExamResultRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->validated());

        return back()->with('status', 'Résultat mis à jour.');
    }
}
