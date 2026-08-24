<?php

namespace App\Domain\Documents\Http\Controllers;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Http\Requests\RejectDossierDocumentRequest;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly LifecycleService $lifecycle,
    ) {}

    public function index(): View
    {
        $students = Student::query()
            ->where('lifecycle_stage', LifecycleStage::Validation->value)
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

        abort_unless($student instanceof Student && $student->lifecycle_stage === LifecycleStage::Validation, 403);

        $document->update([
            'review_status' => $status,
            'rejection_reason' => $reason,
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        if ($status === DocumentReviewStatus::Rejected) {
            $this->lifecycle->transitionTo($student, LifecycleStage::DossierSetup);

            return;
        }

        $activeTypeIds = RequiredDocumentType::query()->where('structure_id', $student->structure_id)->active()->pluck('id');

        $allApproved = $activeTypeIds->isEmpty() ? false : $activeTypeIds->every(
            fn (int $typeId) => Document::query()
                ->where('documentable_type', $student->getMorphClass())
                ->where('documentable_id', $student->id)
                ->where('required_document_type_id', $typeId)
                ->where('is_current', true)
                ->where('review_status', DocumentReviewStatus::Approved)
                ->exists()
        );

        if ($allApproved) {
            $this->lifecycle->transitionTo($student, LifecycleStage::Enrollment);
        }
    }
}
