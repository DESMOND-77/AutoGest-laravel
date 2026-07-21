<?php

namespace App\Domain\Tenancy\Http\Controllers;

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * Super-admin only: validate/suspend/deactivate/delete tenants. Equivalent
 * of the legacy modules/superadmin/dashboard.php, split out of the single
 * do-everything file it used to be.
 */
class StructureManagementController extends Controller
{
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

        $structure->update(['status' => $data['status']]);

        return back()->with('status', 'Statut mis à jour.');
    }

    public function destroy(Structure $structure): RedirectResponse
    {
        $structure->delete();

        return back()->with('status', 'Établissement supprimé.');
    }
}
