<?php

namespace App\Domain\CRM\Http\Controllers;

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Http\Requests\StoreLeadRequest;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Services\LeadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leads,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Lead::class);

        return view('crm.leads.index', [
            'leads' => Lead::query()->latest()->get(),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        Lead::query()->create($request->validated() + ['status' => LeadStatus::New->value]);

        return back()->with('status', 'Prospect ajouté.');
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate(['status' => ['required', new Enum(LeadStatus::class)]]);

        $lead->update(['status' => $data['status']]);

        return back()->with('status', 'Statut mis à jour.');
    }

    public function convert(Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $student = $this->leads->convert($lead);

        return redirect()->route('students.show', $student)->with('status', 'Prospect converti en élève.');
    }
}
