<?php

namespace App\Domain\Audit\Http\Controllers;

use App\Domain\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('user')->latest();

        if (! Auth::user()->hasRole('superadmin')) {
            $query->where('structure_id', Auth::user()->structure_id);
        }

        return view('audit.index', [
            'logs' => $query->paginate(30),
        ]);
    }
}
