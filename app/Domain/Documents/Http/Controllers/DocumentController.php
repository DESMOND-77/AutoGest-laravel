<?php

namespace App\Domain\Documents\Http\Controllers;

use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Http\Requests\StoreDocumentRequest;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function storeForStudent(StoreDocumentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $this->documents->upload(
            $request->file('file'),
            $student,
            DocumentType::from($request->validated('type')),
            Auth::user(),
            $request->validated('expires_at'),
        );

        return back()->with('status', 'Document déposé.');
    }

    public function storeForVehicle(StoreDocumentRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $this->documents->upload(
            $request->file('file'),
            $vehicle,
            DocumentType::from($request->validated('type')),
            Auth::user(),
            $request->validated('expires_at'),
        );

        return back()->with('status', 'Document déposé.');
    }

    public function download(Document $document): StreamedResponse
    {
        if (! Auth::user()->can('view', $document)) {
            throw new AuthorizationException;
        }

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }
}
