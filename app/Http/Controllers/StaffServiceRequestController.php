<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ReferenceSequence;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\ServiceRequestStatusLog;
use App\Notifications\PortalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StaffServiceRequestController extends Controller
{
    /**
     * Display a list of the staff member's service requests.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $statusFilter = $request->query('status');

        $query = ServiceRequest::with(['assignedTo'])
            ->where('user_id', $user->id)
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

        $requests = $query->paginate(15);

        return view('service-requests.index', [
            'requests' => $requests,
            'currentFilter' => $statusFilter,
        ]);
    }

    /**
     * Show the form for creating a new service request.
     */
    public function create(): View
    {
        return view('service-requests.create');
    }

    /**
     * Store a newly created service request with tracking reference and isolation tags.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'unit' => ['required', 'string', 'in:registry,bursary,ict'],
            'category' => ['required', 'string', 'max:60'],
            'subject' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'priority' => ['required', 'string', 'in:low,standard,high,urgent'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        // Auto-tag confidential salary or banking queries
        $isConfidential = false;
        if ($validated['unit'] === ServiceRequest::UNIT_BURSARY) {
            $confidentialCategories = ['salary_enquiry', 'bank_change', 'tax_deduction', 'remuneration'];
            if (in_array(strtolower($validated['category']), $confidentialCategories, true) ||
                str_contains(strtolower($validated['subject']), 'salary') ||
                str_contains(strtolower($validated['subject']), 'bank')) {
                $isConfidential = true;
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('service_requests', 'private');
        }

        $serviceRequest = DB::transaction(function () use ($user, $validated, $isConfidential, $attachmentPath) {
            $year = (int) now()->year;
            $floor = ServiceRequest::query()->whereYear('created_at', $year)->count();
            $sequence = ReferenceSequence::next('service_request', $year, $floor);
            $trackingReference = 'REQ-'.$year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $srv = ServiceRequest::create([
                'tracking_reference' => $trackingReference,
                'user_id' => $user->id,
                'unit' => $validated['unit'],
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'status' => ServiceRequest::STATUS_OPEN,
                'is_confidential' => $isConfidential,
                'attachment_path' => $attachmentPath,
            ]);

            ServiceRequestMessage::create([
                'service_request_id' => $srv->id,
                'user_id' => $user->id,
                'message' => $validated['description'],
                'is_internal' => false,
                'attachment_path' => $attachmentPath,
            ]);

            ServiceRequestStatusLog::create([
                'service_request_id' => $srv->id,
                'actor_id' => $user->id,
                'from_status' => null,
                'to_status' => ServiceRequest::STATUS_OPEN,
                'comment' => 'Request created by staff member.',
            ]);

            return $srv;
        });

        PortalNotifier::sendMany(
            ServiceRequest::unitHandlerUsers($serviceRequest->unit),
            'New service request: '.$serviceRequest->tracking_reference,
            'A new '.$serviceRequest->category.' request has been routed to your unit.',
            route('service-requests.show', $serviceRequest),
            'service_request',
        );

        AuditLog::record('service_request.created', $serviceRequest, [
            'unit' => $serviceRequest->unit,
            'category' => $serviceRequest->category,
            'is_confidential' => (bool) $serviceRequest->is_confidential,
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('status', "Service request {$serviceRequest->tracking_reference} submitted successfully.");
    }

    /**
     * Display the specified service request with message thread and status history.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view-service-request', $serviceRequest);

        $user = $request->user();
        $isHandler = $serviceRequest->isUnitHandler($user);

        $messagesQuery = $serviceRequest->messages()->with('user')->oldest();
        if (! $isHandler) {
            $messagesQuery->where('is_internal', false);
        }
        $messages = $messagesQuery->get();

        $statusLogs = $serviceRequest->statusLogs()->with('actor')->latest('id')->get();

        return view('service-requests.show', [
            'serviceRequest' => $serviceRequest,
            'messages' => $messages,
            'statusLogs' => $statusLogs,
            'isHandler' => $isHandler,
        ]);
    }

    /**
     * Add a message or response to an ongoing service request thread.
     */
    public function reply(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        Gate::authorize('view-service-request', $serviceRequest);

        $user = $request->user();
        $isHandler = $serviceRequest->isUnitHandler($user);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'is_internal' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $isInternal = false;
        if ($isHandler && ! empty($validated['is_internal'])) {
            $isInternal = true;
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('service_requests', 'private');
        }

        DB::transaction(function () use ($serviceRequest, $user, $validated, $isInternal, $attachmentPath, $isHandler): void {
            ServiceRequestMessage::create([
                'service_request_id' => $serviceRequest->id,
                'user_id' => $user->id,
                'message' => $validated['message'],
                'is_internal' => $isInternal,
                'attachment_path' => $attachmentPath,
            ]);

            // If applicant replies to a closed/resolved request, reopen it
            if (! $isHandler && $serviceRequest->isClosed()) {
                $oldStatus = $serviceRequest->status;
                $serviceRequest->status = ServiceRequest::STATUS_OPEN;
                $serviceRequest->save();

                ServiceRequestStatusLog::create([
                    'service_request_id' => $serviceRequest->id,
                    'actor_id' => $user->id,
                    'from_status' => $oldStatus,
                    'to_status' => ServiceRequest::STATUS_OPEN,
                    'comment' => 'Reopened by applicant with additional enquiry.',
                ]);
            } elseif ($isHandler && $serviceRequest->status === ServiceRequest::STATUS_OPEN && ! $isInternal) {
                // If handler replies publicly to an open request, mark in_progress
                $serviceRequest->status = ServiceRequest::STATUS_IN_PROGRESS;
                $serviceRequest->save();

                ServiceRequestStatusLog::create([
                    'service_request_id' => $serviceRequest->id,
                    'actor_id' => $user->id,
                    'from_status' => ServiceRequest::STATUS_OPEN,
                    'to_status' => ServiceRequest::STATUS_IN_PROGRESS,
                    'comment' => 'Response provided by administrative unit.',
                ]);
            }
        });

        if ($isHandler) {
            PortalNotifier::send(
                $serviceRequest->user,
                'Service request updated',
                'Your request '.$serviceRequest->tracking_reference.' has a new response in the portal.',
                route('service-requests.show', $serviceRequest),
                'service_request',
            );
        } else {
            PortalNotifier::sendMany(
                ServiceRequest::unitHandlerUsers($serviceRequest->unit),
                'New message on service request '.$serviceRequest->tracking_reference,
                'The applicant added a new message to their request.',
                route('service-requests.show', $serviceRequest),
                'service_request',
            );
        }

        AuditLog::record('service_request.replied', $serviceRequest, [
            'is_internal' => $isInternal,
            'actor_is_handler' => $isHandler,
        ]);

        return back()->with('status', 'Response added to thread successfully.');
    }
}
