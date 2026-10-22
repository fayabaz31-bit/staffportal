@extends('layouts.app')

@section('title', 'Submit Service Request | NfLV Staff Portal')

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
                <p class="rail-label">SELF-SERVICE</p>
                <a class="rail-link rail-link-active" href="{{ route('service-requests.index') }}"><span class="rail-dot"></span> Service Requests</a>
                <a class="rail-link" href="{{ route('leave.index') }}">Leave Applications</a>
                <a class="rail-link" href="{{ route('payslips.index') }}">My Payslips</a>
                <a class="rail-link" href="{{ route('memos.index') }}">Correspondence</a>
                <a class="rail-link" href="{{ route('profile.show') }}">My Profile</a>
                <div class="rail-divider"></div>
                <div class="rail-bottom"><span class="status-dot"></span> SERVICE DESK ACTIVE</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker"><span>NEW TICKET</span><span>Direct Administrative Routing</span></div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">STAFF SUPPORT</p>
                    <h1>Submit Service Request</h1>
                    <p>Route your enquiry, official document requisition, or technical support ticket directly to the designated administrative unit.</p>
                </section>

                @if ($errors->any())
                    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
                        <strong style="color: #991b1b; font-size: 13px;">Please correct the errors below:</strong>
                        <ul style="margin: 6px 0 0 16px; font-size: 12px; color: #b91c1c;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-top: 20px;">
                    <form method="POST" action="{{ route('service-requests.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                            <div>
                                <label for="unit" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    Administrative Unit <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="unit" name="unit" required style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                                    <option value="">Select Target Unit...</option>
                                    <option value="registry" {{ old('unit') === 'registry' ? 'selected' : '' }}>Registry & Personnel Directorate</option>
                                    <option value="bursary" {{ old('unit') === 'bursary' ? 'selected' : '' }}>Bursary & Finance Division</option>
                                    <option value="ict" {{ old('unit') === 'ict' ? 'selected' : '' }}>ICT Directorate</option>
                                </select>
                            </div>

                            <div>
                                <label for="category" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    Category <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="category" name="category" required style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                                    <option value="">Select Category...</option>
                                    <optgroup label="Registry">
                                        <option value="employment_verification" {{ old('category') === 'employment_verification' ? 'selected' : '' }}>Employment Verification</option>
                                        <option value="service_record" {{ old('category') === 'service_record' ? 'selected' : '' }}>Service Record Attestation</option>
                                        <option value="pension_query" {{ old('category') === 'pension_query' ? 'selected' : '' }}>Pension / RSA Enquiry</option>
                                        <option value="general_registry" {{ old('category') === 'general_registry' ? 'selected' : '' }}>General Registry Enquiry</option>
                                    </optgroup>
                                    <optgroup label="Bursary">
                                        <option value="salary_enquiry" {{ old('category') === 'salary_enquiry' ? 'selected' : '' }}>Salary / Remuneration Enquiry (Confidential)</option>
                                        <option value="bank_change" {{ old('category') === 'bank_change' ? 'selected' : '' }}>Bank Account Update (Confidential)</option>
                                        <option value="tax_deduction" {{ old('category') === 'tax_deduction' ? 'selected' : '' }}>Tax / Deduction Query</option>
                                        <option value="general_bursary" {{ old('category') === 'general_bursary' ? 'selected' : '' }}>General Bursary Enquiry</option>
                                    </optgroup>
                                    <optgroup label="ICT Directorate">
                                        <option value="portal_access" {{ old('category') === 'portal_access' ? 'selected' : '' }}>Portal Access / Credential Issue</option>
                                        <option value="email_support" {{ old('category') === 'email_support' ? 'selected' : '' }}>Official Email Support</option>
                                        <option value="hardware_connectivity" {{ old('category') === 'hardware_connectivity' ? 'selected' : '' }}>Hardware / Connectivity Fault</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div>
                                <label for="priority" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    Priority Level <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="priority" name="priority" required style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low (General inquiry)</option>
                                    <option value="standard" {{ old('priority', 'standard') === 'standard' ? 'selected' : '' }}>Standard (Routine operational request)</option>
                                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High (Time-sensitive matter)</option>
                                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent (Critical blocker)</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: 18px;">
                            <label for="subject" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Subject Summary <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="E.g., Request for Certificate of Service / Salary payment clarification..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px;">
                        </div>

                        <div style="margin-top: 18px;">
                            <label for="description" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Detailed Description <span style="color: #ef4444;">*</span>
                            </label>
                            <textarea id="description" name="description" rows="5" required placeholder="Please provide complete context, reference dates, and specifics to facilitate prompt administrative resolution..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; font-size: 13px;">{{ old('description') }}</textarea>
                        </div>

                        <div style="margin-top: 18px;">
                            <label for="attachment" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Supporting Attachment (Optional: PDF, PNG, JPG, DOC up to 5MB)
                            </label>
                            <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="font-size: 12px; color: #4b5563;">
                        </div>

                        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; margin-top: 20px; font-size: 11px; color: #4b5563;">
                            <strong style="color: #173d35; display: block; margin-bottom: 2px;">Confidentiality & Privacy Assurance:</strong>
                            Requests regarding salary, deductions, or banking details are strictly routed to the Bursary Administrator under isolated access controls. Registry and other departments cannot view confidential Bursary communications.
                        </div>

                        <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                            <a href="{{ route('service-requests.index') }}" class="button" style="padding: 10px 20px; font-size: 12px; text-decoration: none; background: #ffffff; color: #374151; border: 1px solid #d1d5db;">
                                Cancel
                            </a>
                            <button type="submit" class="button button-primary" style="padding: 10px 24px; font-size: 12px;">
                                Submit Service Request
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
@endsection
