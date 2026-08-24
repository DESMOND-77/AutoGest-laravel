<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Exceptions\InvalidStageTransition;
use App\Domain\Students\Http\Requests\UploadDossierDocumentRequest;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDossierController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly LifecycleService $lifecycle,
    ) {}

    public function show(): View
    {
        $student = $this->currentStudent();

        $types = RequiredDocumentType::query()->active()->ordered()->get();

        $current = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->whereNotNull('required_document_type_id')
            ->get()
            ->keyBy('required_document_type_id');

        return view('eleve.dossier', [
            'student' => $student,
            'types' => $types,
            'documentsByType' => $current,
            'canSubmit' => $types->every(fn ($type) => $current->has($type->id)),
        ]);
    }

    public function upload(UploadDossierDocumentRequest $request, RequiredDocumentType $requiredDocumentType): RedirectResponse
    {
        $student = $this->currentStudent();

        abort_unless($requiredDocumentType->structure_id === $student->structure_id, 404);

        $this->documents->upload(
            $request->file('file'),
            $student,
            DocumentType::Other,
            Auth::user(),
            null,
            $requiredDocumentType,
        );

        return back()->with('status', 'Document déposé.');
    }

    public function submit(): RedirectResponse
    {
        $student = $this->currentStudent();

        $missing = RequiredDocumentType::query()->active()->get()->reject(
            fn (RequiredDocumentType $type) => Document::query()
                ->where('documentable_type', $student->getMorphClass())
                ->where('documentable_id', $student->id)
                ->where('required_document_type_id', $type->id)
                ->exists()
        );

        if ($missing->isNotEmpty()) {
            return back()->withErrors(['dossier' => 'Merci de déposer toutes les pièces requises avant de soumettre votre dossier.']);
        }

        try {
            $this->lifecycle->transitionTo($student, LifecycleStage::Validation);
        } catch (InvalidStageTransition) {
            return back()->withErrors(['dossier' => 'Votre dossier n\'est pas dans un état permettant la soumission.']);
        }

        return redirect()->route('eleve.dossier.show')->with('status', 'Dossier soumis pour revue.');
    }

    private function currentStudent(): Student
    {
        return Student::query()->where('user_id', Auth::id())->firstOrFail();
    }
}
