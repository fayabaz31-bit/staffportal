<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffPayslipController extends Controller
{
    public function index(Request $request): View
    {
        $payslips = $request->user()
            ->payslips()
            ->where('status', Payslip::STATUS_PUBLISHED)
            ->latest('payroll_period')
            ->paginate(12);

        return view('payslips.index', [
            'payslips' => $payslips,
        ]);
    }

    public function download(Request $request, Payslip $payslip): StreamedResponse
    {
        $user = $request->user();

        // Strict authorization: only the authenticated owner can access their published payslip
        if ($user->cannot('view-payslip', $payslip)) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($payslip->storage_path)) {
            abort(404, 'The requested document could not be found.');
        }

        // Track download audit event
        $payslip->increment('download_count');
        $payslip->update(['last_downloaded_at' => now()]);

        AuditLog::record('payslip.downloaded', $payslip, [
            'payroll_period' => $payslip->payroll_period,
        ]);

        $safeDownloadFilename = sprintf(
            'Payslip_%s_%s.pdf',
            $payslip->payroll_period,
            preg_replace('/[^A-Za-z0-9_-]/', '', $user->staff_number)
        );

        return Storage::disk('local')->download(
            $payslip->storage_path,
            $safeDownloadFilename,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
