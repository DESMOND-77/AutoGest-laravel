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
use Illuminate\View\View;
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

    /**
     * The viewer page: an <iframe> pointing at stream() below, plus explicit
     * download/print controls. Kept separate from download() — that one
     * always forces an attachment (Content-Disposition: attachment), which
     * would make the browser save the file instead of displaying it here.
     */
    public function show(Document $document): View
    {
        if (! Auth::user()->can('view', $document)) {
            throw new AuthorizationException;
        }

        return view('documents.show', ['document' => $document]);
    }

    public function stream(Document $document): StreamedResponse
    {
        if (! Auth::user()->can('view', $document)) {
            throw new AuthorizationException;
        }

        return Storage::disk($document->disk)->response($document->path, $document->original_name);
    }
}
