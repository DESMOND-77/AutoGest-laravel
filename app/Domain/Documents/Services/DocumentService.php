<?php

namespace App\Domain\Documents\Services;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DossierStatusService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the legacy pattern of loose file-path columns on `eleves`
 * (photo, cni_path, justif_domicile...) - no history, and re-uploading a
 * document silently threw the old file away. Every upload here becomes a
 * new version; the previous one is kept, just no longer flagged current.
 *
 * When $requiredDocumentType is given (student dossier uploads), the
 * "previous version" lookup keys on required_document_type_id instead of
 * DocumentType - several dossier pieces can share the same generic
 * DocumentType::Other, so type alone can't tell them apart. Passing it also
 * resets review_status to Pending on the new row: a fresh upload always
 * needs a fresh review, even if the version it replaces was Approved.
 */
class DocumentService
{
    public function __construct(
        private readonly DossierStatusService $dossierStatus,
    ) {}

    public function upload(
        UploadedFile $file,
        Model $documentable,
        DocumentType $type,
        ?User $uploadedBy = null,
        ?string $expiresAt = null,
        ?RequiredDocumentType $requiredDocumentType = null,
    ): Document {
        return DB::transaction(function () use ($file, $documentable, $type, $uploadedBy, $expiresAt, $requiredDocumentType) {
            $query = Document::query()
                ->where('documentable_type', $documentable->getMorphClass())
                ->where('documentable_id', $documentable->getKey())
                ->where('is_current', true);

            $query = $requiredDocumentType
                ? $query->where('required_document_type_id', $requiredDocumentType->id)
                : $query->where('type', $type->value);

            $previous = $query->first();

            $previous?->update(['is_current' => false]);

            $path = $file->store('documents', 'local');

            $document = Document::query()->create([
                'documentable_type' => $documentable->getMorphClass(),
                'documentable_id' => $documentable->getKey(),
                'type' => $type->value,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'version' => ($previous?->version ?? 0) + 1,
                'is_current' => true,
                'uploaded_by' => $uploadedBy?->id,
                'expires_at' => $expiresAt,
                'required_document_type_id' => $requiredDocumentType?->id,
                'review_status' => DocumentReviewStatus::Pending,
            ]);

            if ($documentable instanceof Student) {
                $this->dossierStatus->syncFor($documentable);
            }

            return $document;
        });
    }
}
