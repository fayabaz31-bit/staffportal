<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusLog;
use App\Models\User;
use App\Notifications\PortalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitServiceRequestController extends Controller
{
    /**
     * Display a list of service requests assigned to a specific administrative unit.
     */
    public function index(Request $request, string $unit): View
    {
        $this->authorizeUnitAccess($unit);

        $statusFilter = $request->query('status');
        $priorityFilter = $request->query('priority');

        $query = ServiceRequest::with(['user.profile.department', 'assignedTo'])
            ->where('unit', $unit)
            ->latest();

        if ($statusFilter && in_array($statusFilter, [
            ServiceRequest::STATUS_OPEN,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_ESCALATED,
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_CLOSED,
        ], true)) {
            $query->where('status', $statusFilter);
        }

        if ($priorityFilter && in_array($priorityFilter, [
            ServiceRequest::PRIORITY_LOW,
            ServiceRequest::PRIORITY_STANDARD,
            ServiceRequest::PRIORITY_HIGH,
            ServiceRequest::PRIORITY_URGENT,
        ], true)) {
            $query->where('priority', $priorityFilter);
        }

        $requests = $query->paginate(15);

        // Fetch eligible officers for assignment
        $officers = User::where('status', 'active')->orderBy('name')->get();

        return view('service-requests.unit-index', [
            'unit' => $unit,
            'requests' => $requests,
            'officers' => $officers,
            'currentStatus' => $statusFilter,
            'currentPriority' => $priorityFilter,
        ]);
    }

    /**
     * Update the workflow status or assignment of a service request.
     */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorizeUnitAccess($serviceRequest->unit);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,escalated,resolved,closed'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'assigned_to_id' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $serviceRequest->status;

        DB::transaction(function () use ($serviceRequest, $request, $validated, $oldStatus): void {
            $serviceRequest->status = $validated['status'];

            if ($validated['status'] === ServiceRequest::STATUS_RESOLVED && ! $serviceRequest->resolved_at) {
                $serviceRequest->resolved_at = now();
            }

            if ($validated['status'] === ServiceRequest::STATUS_CLOSED && ! $serviceRequest->closed_at) {
                $serviceRequest->closed_at = now();
            }

            if (! empty($validated['assigned_to_id'])) {
                $serviceRequest->assigned_to_id = $validated['assigned_to_id'];
                $serviceRequest->assigned_at = now();
            }

            $serviceRequest->save();

            ServiceRequestStatusLog::create([
                'service_request_id' => $serviceRequest->id,
                'actor_id' => $request->user()->id,
                'from_status' => $oldStatus,
                'to_status' => $validated['status'],
                'comment' => $validated['comment'] ?? "Status updated to {$validated['status']}.",
            ]);
        });

        PortalNotifier::send(
            $serviceRequest->user,
            'Service request status updated',
            'Your request '.$serviceRequest->tracking_reference.' is now '.str_replace('_', ' ', $validated['status']).'.',
            route('service-requests.show', $serviceRequest),
            'service_request',
        );

        AuditLog::record('service_request.status_changed', $serviceRequest, [
            'from_status' => $oldStatus,
            'to_status' => $validated['status'],
        ]);

        return back()->with('status', "Request {$serviceRequest->tracking_reference} status updated to {$validated['status']}.");
    }

    /**
     * Authorize access according to unit domain boundaries.
     */
    private function authorizeUnitAccess(string $unit): void
    {
        if ($unit === ServiceRequest::UNIT_REGISTRY) {
            Gate::authorize('manage-registry-requests');
        } elseif ($unit === ServiceRequest::UNIT_BURSARY) {
            Gate::authorize('manage-bursary-requests');
        } elseif ($unit === ServiceRequest::UNIT_ICT) {
            Gate::authorize('manage-ict-requests');
        } else {
            abort(404, 'Unknown administrative unit.');
        }
    }
}
