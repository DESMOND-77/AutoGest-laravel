<?php

namespace App\Domain\Documents\Http\Controllers;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Http\Requests\RejectDossierDocumentRequest;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Review now happens while the student is still assembling their dossier
 * (DossierSetup), not after a formal "submission" - StudentDossierController
 * ::submit() requires every required piece to already be Approved before it
 * lets the student submit at all, and submission itself is what advances the
 * student straight through to Enrollment. So approving/rejecting a single
 * document here never moves the student's lifecycle_stage by itself; it only
 * ever updates the Document row.
 */
class DocumentReviewController extends Controller
{
    public function index(): View
    {
        $students = Student::query()
            ->where('lifecycle_stage', LifecycleStage::DossierSetup->value)
            ->whereHas('documents', fn ($query) => $query->where('is_current', true)->whereNotNull('required_document_type_id'))
            ->with(['documents' => fn ($query) => $query->where('is_current', true)->whereNotNull('required_document_type_id')->with('requiredDocumentType')])
            ->get();

        return view('students.dossier-review', ['students' => $students]);
    }

    public function approve(Document $document): RedirectResponse
    {
        $this->authorize('review', $document);

        $this->decide($document, DocumentReviewStatus::Approved);

        return back()->with('status', 'Document approuvé.');
    }

    public function reject(RejectDossierDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->decide($document, DocumentReviewStatus::Rejected, $request->validated('reason'));

        return back()->with('status', 'Document rejeté.');
    }

    private function decide(Document $document, DocumentReviewStatus $status, ?string $reason = null): void
    {
        $student = $document->documentable;

        abort_unless($student instanceof Student && $student->lifecycle_stage === LifecycleStage::DossierSetup, 403);
        abort_unless($document->required_document_type_id !== null, 404);

        $document->update([
            'review_status' => $status,
            'rejection_reason' => $reason,
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);
    }
}
