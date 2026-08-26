<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Exceptions\InvalidStageTransition;
use App\Domain\Students\Http\Requests\UploadDossierDocumentRequest;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DocumentBundleService;
use App\Domain\Students\Services\DossierStatusService;
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
        private readonly DocumentBundleService $bundle,
        private readonly DossierStatusService $dossierStatus,
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
            'canSubmit' => $types->isNotEmpty() && $types->every(function ($type) use ($current) {
                $document = $current->get($type->id);

                return $document && $document->review_status->value === DocumentReviewStatus::Approved->value;
            }),
            DocumentReviewStatus::class => DocumentReviewStatus::class,
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

        // Récupérer tous les types actifs
        $types = RequiredDocumentType::query()->active()->get();

        if ($types->isEmpty()) {
            return back()->withErrors(['dossier' => 'Aucune pièce requise n\'est définie.']);
        }

        // Récupérer en une seule requête les documents actuels approuvés pour cet étudiant
        $approvedDocs = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->where('review_status', DocumentReviewStatus::Approved)
            ->whereNotNull('required_document_type_id')
            ->pluck('required_document_type_id')
            ->all();

        $missingOrNotApproved = $types->reject(fn ($type) => in_array($type->id, $approvedDocs));

        if ($missingOrNotApproved->isNotEmpty()) {
            return back()->withErrors([
                'dossier' => 'Toutes les pièces doivent être validées avant la soumission du dossier. Veuillez vérifier les pièces en attente ou rejetées.',
            ]);
        }

        $zipPath = $this->bundle->bundle($student);
        $student->setDocumentSubmitted(true);
        $student->setDocumentsZipPath($zipPath);
        $student->save();
        $this->dossierStatus->syncFor($student);

        try {
            // Every required piece is already Approved by this point (checked
            // above), so submission and enrollment happen together: there is
            // nothing left for an admin to review once a student is allowed
            // to submit at all. Validation is still passed through - every
            // lifecycle_stage change goes through LifecycleService, never a
            // direct write - but it's not a state anyone needs to act on.
            $this->lifecycle->transitionTo($student, LifecycleStage::Validation);
            $this->lifecycle->transitionTo($student, LifecycleStage::Enrollment);
        } catch (InvalidStageTransition) {
            return back()->withErrors(['dossier' => 'Votre dossier n\'est pas dans un état permettant la soumission.']);
        }

        return redirect()->route('eleve.dossier.show')->with('status', 'Dossier validé, votre inscription est confirmée.');
    }

    private function currentStudent(): Student
    {
        return Student::query()->where('user_id', Auth::id())->firstOrFail();
    }
}
