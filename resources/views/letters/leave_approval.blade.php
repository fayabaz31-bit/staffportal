<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Official Leave Approval Letter - {{ $referenceNumber }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.6;
            color: #1a1a1a;
            margin: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #173d35;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 18px;
            color: #173d35;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            font-size: 13px;
            font-weight: normal;
            color: #4a5568;
            margin: 0 0 4px 0;
        }
        .header p {
            font-size: 11px;
            color: #718096;
            margin: 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 24px;
        }
        .meta-table td {
            vertical-align: top;
            font-size: 12px;
        }
        .meta-right {
            text-align: right;
        }
        .subject-box {
            background-color: #f7fafc;
            border-left: 4px solid #173d35;
            padding: 10px 14px;
            margin: 20px 0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
        }
        .content {
            margin-bottom: 30px;
            text-align: justify;
        }
        .content p {
            margin-bottom: 14px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table th, .details-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            font-size: 12px;
        }
        .details-table th {
            background-color: #edf2f7;
            text-align: left;
            width: 35%;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-box {
            float: right;
            width: 250px;
            text-align: center;
            border-top: 1px solid #a0aec0;
            padding-top: 8px;
        }
        .signature-box strong {
            display: block;
            font-size: 12px;
            color: #173d35;
        }
        .signature-box span {
            font-size: 11px;
            color: #718096;
        }
        .footer {
            margin-top: 60px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 10px;
            color: #a0aec0;
            text-align: center;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nigeria French Language Village</h1>
        <h2>Inter-University Centre for French Studies — Badagry, Lagos State</h2>
        <p>Office of the Registrar & Personnel Management Directorate</p>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>Ref:</strong> {{ $referenceNumber }}<br>
                <strong>Date:</strong> {{ $leave->approved_at ? $leave->approved_at->format('d F Y') : now()->format('d F Y') }}
            </td>
            <td class="meta-right">
                <strong>To:</strong> {{ $leave->user->name }}<br>
                <strong>Staff ID:</strong> {{ $leave->user->staff_number }}<br>
                <strong>Department:</strong> {{ $leave->user->profile?->department?->name ?? 'General Service' }}
            </td>
        </tr>
    </table>

    <div class="subject-box">
        APPROVAL OF {{ strtoupper($leave->leaveType->name) }} ({{ $leave->days_requested }} WORKING DAYS)
    </div>

    <div class="content">
        <p>Dear {{ $leave->user->name }},</p>

        <p>
            I am pleased to convey the approval of the Management for your application for 
            <strong>{{ $leave->leaveType->name }}</strong> for a period of <strong>{{ $leave->days_requested }} working day(s)</strong>.
        </p>

        <table class="details-table">
            <tr>
                <th>Leave Category</th>
                <td>{{ $leave->leaveType->name }}</td>
            </tr>
            <tr>
                <th>Effective Start Date</th>
                <td>{{ $leave->start_date->format('l, d F Y') }}</td>
            </tr>
            <tr>
                <th>Effective End Date</th>
                <td>{{ $leave->end_date->format('l, d F Y') }}</td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>{{ $leave->days_requested }} Business Day(s)</td>
            </tr>
            <tr>
                <th>Expected Resumption Date</th>
                <td>{{ $leave->end_date->addDay()->isWeekend() ? ($leave->end_date->addDay()->isSaturday() ? $leave->end_date->addDays(3)->format('l, d F Y') : $leave->end_date->addDays(2)->format('l, d F Y')) : $leave->end_date->addDay()->format('l, d F Y') }}</td>
            </tr>
        </table>

        <p>
            You are expected to resume duty promptly upon the expiration of this leave period and submit a formal resumption of duty notice through your Head of Department.
        </p>

        <p>
            Please accept our warm regards and best wishes.
        </p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <strong>{{ $approver->name }}</strong>
            <span>{{ $approver->role->label() }}</span><br>
            <span>For: Director & Management Board</span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        This is an official document generated by the Nigeria French Language Village Staff Self-Service Portal.<br>
        Document Security Reference: {{ $referenceNumber }} &bull; Issued at: {{ $leave->approved_at?->toIso8601String() ?? now()->toIso8601String() }}
    </div>
</body>
</html>
