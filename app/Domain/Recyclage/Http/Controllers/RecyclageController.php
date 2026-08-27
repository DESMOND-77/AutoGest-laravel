<?php

namespace App\Domain\Recyclage\Http\Controllers;

use App\Domain\Recyclage\Events\RecyclageEntryRecorded;
use App\Domain\Recyclage\Http\Requests\StoreRecyclageEntryRequest;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;

class RecyclageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RecyclageEntry::class);

        return view('recyclage.index', [
            'entries' => RecyclageEntry::query()->with('instructor')->latest('session_date')->paginate(20),
            'instructors' => User::role('moniteur')->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRecyclageEntryRequest $request): RedirectResponse
    {
        $entry = RecyclageEntry::query()->create($request->validated());

        Event::dispatch(new RecyclageEntryRecorded(
            $entry,
            (float) $entry->amount,
            $entry->full_name,
            $entry->session_date->toDateString(),
        ));

        return redirect()->route('recyclage.index')->with('status', 'Entrée enregistrée.');
    }

    public function destroy(RecyclageEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);

        $entry->delete();

        return redirect()->route('recyclage.index')->with('status', 'Entrée supprimée.');
    }
}
