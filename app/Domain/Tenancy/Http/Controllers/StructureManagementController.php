<?php

namespace App\Domain\Tenancy\Http\Controllers;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * Super-admin only: validate/suspend/deactivate/delete tenants. Equivalent
 * of the legacy modules/superadmin/dashboard.php, split out of the single
 * do-everything file it used to be.
 */
class StructureManagementController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $structures = Structure::query()
            ->withCount('users')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenancy.superadmin.index', [
            'structures' => $structures,
            'statuses' => StructureStatus::cases(),
            'currentStatus' => $status,
        ]);
    }

    public function updateStatus(Request $request, Structure $structure): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', new Enum(StructureStatus::class)],
        ]);

        $oldStatus = $structure->status->value;
        $structure->update(['status' => $data['status']]);

        $this->audit->log(
            'structure.status_updated',
            $structure,
            ['status' => $oldStatus],
            ['status' => $data['status']],
            Auth::user(),
        );

        return back()->with('status', 'Statut mis à jour.');
    }

    public function destroy(Structure $structure): RedirectResponse
    {
        $this->audit->log('structure.deleted', $structure, $structure->only(['name', 'status']), [], Auth::user());

        $structure->delete();

        return back()->with('status', 'Établissement supprimé.');
    }
}
