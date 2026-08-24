<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Http\Requests\StoreRequiredDocumentTypeRequest;
use App\Domain\Students\Http\Requests\UpdateRequiredDocumentTypeRequest;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RequiredDocumentTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RequiredDocumentType::class);

        return view('settings.document-types', [
            'types' => RequiredDocumentType::query()->ordered()->get(),
        ]);
    }

    public function store(StoreRequiredDocumentTypeRequest $request): RedirectResponse
    {
        RequiredDocumentType::query()->create($request->validated() + [
            'position' => (int) RequiredDocumentType::query()->max('position') + 1,
        ]);

        return back()->with('status', 'Pièce requise ajoutée.');
    }

    public function update(UpdateRequiredDocumentTypeRequest $request, RequiredDocumentType $requiredDocumentType): RedirectResponse
    {
        $requiredDocumentType->update($request->validated());

        return back()->with('status', 'Pièce requise mise à jour.');
    }
}
