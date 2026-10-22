@extends('layouts.app')

@section('title', 'Service Request ' . $serviceRequest->tracking_reference . ' | NfLV Staff Portal')

@section('content')
    <div class="dashboard-page">
        <header class="dashboard-header">
            <a class="wordmark dashboard-wordmark" href="{{ route('dashboard') }}">
                <span class="wordmark-name">NfLV</span>
                <span class="wordmark-divider" aria-hidden="true"></span>
                <span class="wordmark-caption">Staff Portal</span>
            </a>
            <div class="account-tools">
                <span class="account-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">Sign out</button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Staff navigation">
                <p class="rail-label">SERVICE DESK</p>
                <a class="rail-link rail-link-active" href="{{ route('service-requests.index') }}"><span class="rail-dot"></span> All Requests</a>
                @if ($isHandler)
                    <a class="rail-link" href="{{ route('unit.service-requests.index', $serviceRequest->unit) }}">Unit Queue ({{ strtoupper($serviceRequest->unit) }})</a>
                @endif
                <a class="rail-link" href="{{ route('leave.index') }}">Leave Applications</a>
                <a class="rail-link" href="{{ route('payslips.index') }}">My Payslips</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> TICKET ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>TICKET DETAILS</span><span>{{ $serviceRequest->tracking_reference }}</span></div>
                
                <section class="dashboard-welcome" style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p class="eyebrow eyebrow-dark">{{ strtoupper($serviceRequest->unit) }} DIVISION</p>
                        <h1>{{ $serviceRequest->subject }}</h1>
                        <p>Submitted by <strong>{{ $serviceRequest->user->name }}</strong> ({{ $serviceRequest->user->staff_number }}) on {{ $serviceRequest->created_at->format('l, d F Y, H:i') }}.</p>
                    </div>
                    <div style="text-align: right;">
                        @if ($serviceRequest->status === 'resolved')
                            <span style="font-size: 11px; font-weight: 700; color: #047857; background: #d1fae5; padding: 4px 12px; border-radius: 9999px;">RESOLVED</span>
                        @elseif ($serviceRequest->status === 'closed')
                            <span style="font-size: 11px; font-weight: 700; color: #4b5563; background: #e5e7eb; padding: 4px 12px; border-radius: 9999px;">CLOSED</span>
                        @elseif ($serviceRequest->status === 'in_progress')
                            <span style="font-size: 11px; font-weight: 700; color: #1d4ed8; background: #dbeafe; padding: 4px 12px; border-radius: 9999px;">IN PROGRESS</span>
                        @elseif ($serviceRequest->status === 'escalated')
                            <span style="font-size: 11px; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 4px 12px; border-radius: 9999px;">ESCALATED</span>
                        @else
                            <span style="font-size: 11px; font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 4px 12px; border-radius: 9999px;">OPEN</span>
                        @endif

                        @if ($serviceRequest->is_confidential)
                            <div style="margin-top: 6px;">
                                <span style="font-size: 9px; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">Confidential Case</span>
                            </div>
                        @endif
                    </div>
                </section>

                @if (session('status'))
                    <p class="profile-notice" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="field-error" style="margin: 12px 0; font-size: 13px;" role="alert">{{ session('error') }}</p>
                @endif

                <!-- Metadata Strip -->
                <section class="identity-strip" style="margin-top: 18px;" aria-label="Ticket metadata">
                    <div><span class="detail-label">CATEGORY</span><strong>{{ str_replace('_', ' ', $serviceRequest->category) }}</strong></div>
                    <div><span class="detail-label">PRIORITY</span><strong style="text-transform: uppercase;">{{ $serviceRequest->priority }}</strong></div>
                    <div><span class="detail-label">ASSIGNED OFFICER</span><strong>{{ $serviceRequest->assignedTo?->name ?? 'Unassigned' }}</strong></div>
                    <div><span class="detail-label">STATUS</span><strong>{{ strtoupper(str_replace('_', ' ', $serviceRequest->status)) }}</strong></div>
                </section>

                <!-- Administrative Controls for Unit Handlers -->
                @if ($isHandler)
                    <section style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 18px; margin-top: 24px;">
                        <h2 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #1e293b; margin: 0 0 12px 0;">
                            Administrative Unit Control Panel
                        </h2>

                        <form method="POST" action="{{ route('unit.service-requests.update-status', $serviceRequest) }}" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;">
                            @csrf
                            <div style="flex: 1; min-width: 180px;">
                                <label for="admin_status" style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px;">Update Status:</label>
                                <select id="admin_status" name="status" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 12px;">
                                    <option value="open" {{ $serviceRequest->status === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $serviceRequest->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="escalated" {{ $serviceRequest->status === 'escalated' ? 'selected' : '' }}>Escalate Ticket</option>
                                    <option value="resolved" {{ $serviceRequest->status === 'resolved' ? 'selected' : '' }}>Mark Resolved</option>
                                    <option value="closed" {{ $serviceRequest->status === 'closed' ? 'selected' : '' }}>Close Ticket</option>
                                </select>
                            </div>

                            <div style="flex: 2; min-width: 250px;">
                                <label for="admin_comment" style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px;">Status Transition Note:</label>
                                <input type="text" id="admin_comment" name="comment" placeholder="Optional audit explanation..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 12px;">
                            </div>

                            <div>
                                <button type="submit" class="button button-primary" style="padding: 8px 18px; font-size: 11px;">
                                    Update Ticket Status
                                </button>
                            </div>
                        </form>
                    </section>
                @endif

                <!-- Message & Conversation Thread -->
                <section style="margin-top: 30px;">
                    <h2 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #173d35; margin-bottom: 16px;">
                        Communication Thread ({{ $messages->count() }})
                    </h2>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        @foreach ($messages as $msg)
                            @if ($msg->is_internal)
                                <!-- Internal note (Only handlers see this) -->
                                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 14px 18px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div>
                                            <strong style="color: #92400e; font-size: 12px;">{{ $msg->user->name }}</strong>
                                            <span style="font-size: 10px; font-weight: 700; background: #fde68a; color: #78350f; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">INTERNAL NOTE &bull; UNIT ONLY</span>
                                        </div>
                                        <span style="font-size: 11px; color: #b45309;">{{ $msg->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div style="font-size: 13px; color: #78350f; line-height: 1.5; white-space: pre-wrap;">{{ $msg->message }}</div>
                                </div>
                            @else
                                <!-- Standard Public Reply -->
                                <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div>
                                            <strong style="color: #111827; font-size: 13px;">{{ $msg->user->name }}</strong>
                                            @if ($msg->user_id === $serviceRequest->user_id)
                                                <span style="font-size: 10px; font-weight: 600; background: #f3f4f6; color: #4b5563; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">APPLICANT</span>
                                            @else
                                                <span style="font-size: 10px; font-weight: 600; background: #ecfdf5; color: #065f46; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">OFFICER</span>
                                            @endif
                                        </div>
                                        <span style="font-size: 11px; color: #6b7280;">{{ $msg->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div style="font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-wrap;">{{ $msg->message }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>

                <!-- Reply Form -->
                <section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-top: 30px;">
                    <h3 style="margin: 0 0 14px 0; font-size: 13px; text-transform: uppercase; color: #173d35;">
                        Post Response / Follow-up
                    </h3>

                    <form method="POST" action="{{ route('service-requests.reply', $serviceRequest) }}" enctype="multipart/form-data">
                        @csrf
                        <div style="margin-bottom: 12px;">
                            <textarea name="message" rows="4" required placeholder="Type your message or response here..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; font-size: 13px;">{{ old('message') }}</textarea>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                            <div>
                                @if ($isHandler)
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #92400e; font-weight: 600; cursor: pointer;">
                                        <input type="checkbox" name="is_internal" value="1">
                                        Post as internal note (visible only to administrative unit team)
                                    </label>
                                @endif
                            </div>

                            <button type="submit" class="button button-primary" style="padding: 9px 22px; font-size: 12px;">
                                Send Response
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Status History Timeline -->
                @if ($statusLogs->isNotEmpty())
                    <section style="margin-top: 36px;">
                        <h3 style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 12px;">
                            Lifecycle & Audit Trail
                        </h3>

                        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px;">
                            <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #4b5563;">
                                @foreach ($statusLogs as $log)
                                    <li style="margin-bottom: 8px;">
                                        <strong>{{ $log->created_at->format('d M Y, H:i') }}</strong> —
                                        Status set to <strong style="text-transform: uppercase; color: #173d35;">{{ $log->to_status }}</strong>
                                        by <strong>{{ $log->actor->name }}</strong>:
                                        <em>{{ $log->comment }}</em>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </div>
@endsection
