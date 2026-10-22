<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Read-only, permission-scoped audit trail viewer (PRD §4.9, AC-10/AC-15).
     */
    public function index(Request $request): View
    {
        Gate::authorize('view-audit-log');

        $action = trim($request->string('action')->toString());
        $actor = trim($request->string('actor')->toString());

        $logs = AuditLog::query()
            ->with('actor')
            ->when($action !== '', fn (Builder $query) => $query->where('action', 'like', $action.'%'))
            ->when($actor !== '', fn (Builder $query) => $query->whereHas('actor', function (Builder $userQuery) use ($actor): void {
                $userQuery->where('name', 'like', '%'.$actor.'%')
                    ->orWhere('staff_number', 'like', '%'.$actor.'%');
            }))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'actionFilter' => $action,
            'actorFilter' => $actor,
        ]);
    }
}
