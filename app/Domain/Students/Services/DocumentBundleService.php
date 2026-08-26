<?php

namespace App\Domain\Students\Services;

use App\Domain\Documents\Models\Document;
use App\Domain\Students\Models\Student;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Bundles every current required-piece document into a single downloadable
 * ZIP archive, named {structure_id}_{student_id}_documents.zip - unique per
 * student (a literal "{structure_id}_eleve_documents" name, as originally
 * described, would collide across every student of the same school).
 * Called once, from StudentDossierController::submit().
 */
class DocumentBundleService
{
    public function bundle(Student $student): string
    {
        $documents = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->whereNotNull('required_document_type_id')
            ->with('requiredDocumentType')
            ->get();

        Storage::disk('local')->makeDirectory('dossiers');

        $relativePath = "dossiers/{$student->structure_id}_{$student->id}_documents.zip";
        $absolutePath = Storage::disk('local')->path($relativePath);

        $zip = new ZipArchive;
        $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $sourcePath = Storage::disk($document->disk)->path($document->path);
            $entryName = ($document->requiredDocumentType?->label ?? $document->type->label()).'_'.$document->original_name;
            $zip->addFile($sourcePath, $entryName);
        }

        $zip->close();

        return $relativePath;
    }
}
