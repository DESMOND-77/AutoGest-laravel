<?php

namespace App\Domain\Documents\Services;

use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the legacy pattern of loose file-path columns on `eleves`
 * (photo, cni_path, justif_domicile...) — no history, and re-uploading a
 * document silently threw the old file away. Every upload here becomes a
 * new version; the previous one is kept, just no longer flagged current.
 * Files are stored on the private 'local' disk, never web-reachable
 * directly — download only goes through DocumentController's policy check.
 */
class DocumentService
{
    public function upload(UploadedFile $file, Model $documentable, DocumentType $type, ?User $uploadedBy = null, ?string $expiresAt = null): Document
    {
        return DB::transaction(function () use ($file, $documentable, $type, $uploadedBy, $expiresAt) {
            $previous = Document::query()
                ->where('documentable_type', $documentable->getMorphClass())
                ->where('documentable_id', $documentable->getKey())
                ->where('type', $type->value)
                ->where('is_current', true)
                ->first();

            $previous?->update(['is_current' => false]);

            $path = $file->store('documents', 'local');

            return Document::query()->create([
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
            ]);
        });
    }
}
