<?php

namespace App\Domain\Students\Services;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;

/**
 * The only place allowed to change a student's dossier_status. Unlike
 * lifecycle_stage (a manually-triggered, guarded state machine), dossier_status
 * is purely derived from document state: call syncFor() after anything that
 * could change the answer (a document is uploaded, a document is
 * approved/rejected, a required type is added/updated, or the dossier is
 * submitted) and it recomputes and persists the correct value.
 */
class DossierStatusService
{
    public function syncFor(Student $student): Student
    {
        $target = $this->computeStatus($student);

        if ($student->dossier_status !== $target) {
            $student->setDossierStatus($target);
            $student->save();
        }

        return $student;
    }

    private function computeStatus(Student $student): DossierStatus
    {
        if ($student->document_submitted) {
            return DossierStatus::Submitted;
        }

        $types = RequiredDocumentType::query()->active()->get();

        if ($types->isEmpty()) {
            return DossierStatus::Incomplete;
        }

        $documents = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->whereNotNull('required_document_type_id')
            ->get()
            ->keyBy('required_document_type_id');

        $allUploaded = $types->every(fn (RequiredDocumentType $type) => $documents->has($type->id));

        if (! $allUploaded) {
            return DossierStatus::Incomplete;
        }

        $allApproved = $types->every(
            fn (RequiredDocumentType $type) => $documents->get($type->id)->review_status === DocumentReviewStatus::Approved
        );

        return $allApproved ? DossierStatus::Validated : DossierStatus::Complete;
    }
}
