<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BursaryPayslipBatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IctAccountController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficialLetterController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\PersonnelDirectoryController;
use App\Http\Controllers\PersonnelImportController;
use App\Http\Controllers\ProfileChangeRequestController;
use App\Http\Controllers\RegistryCorrespondenceController;
use App\Http\Controllers\RegistryProfileChangeRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffLeaveController;
use App\Http\Controllers\StaffPayslipController;
use App\Http\Controllers\StaffProfileController;
use App\Http\Controllers\StaffServiceRequestController;
use App\Http\Controllers\UnitServiceRequestController;
use App\Http\Controllers\WorkAssignmentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [StaffProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/contact', [StaffProfileController::class, 'updateContact'])->name('profile.contact.update');
    Route::post('/profile/change-requests', [ProfileChangeRequestController::class, 'store'])
        ->name('profile.change-requests.store');
    Route::get('/memos', [MemoController::class, 'index'])->name('memos.index');
    Route::get('/payslips', [StaffPayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{payslip}/download', [StaffPayslipController::class, 'download'])->name('payslips.download');
    Route::get('/leave', [StaffLeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [StaffLeaveController::class, 'store'])->name('leave.store');
    Route::post('/leave/{leave}/cancel', [StaffLeaveController::class, 'cancel'])->name('leave.cancel');
    Route::get('/letters/{letter}/download', [OfficialLetterController::class, 'download'])->name('letters.download');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/service-requests', [StaffServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::get('/service-requests/create', [StaffServiceRequestController::class, 'create'])->name('service-requests.create');
    Route::post('/service-requests', [StaffServiceRequestController::class, 'store'])->name('service-requests.store');
    Route::get('/service-requests/{serviceRequest}', [StaffServiceRequestController::class, 'show'])->name('service-requests.show');
    Route::post('/service-requests/{serviceRequest}/reply', [StaffServiceRequestController::class, 'reply'])->name('service-requests.reply');
});

// Duty assignment and tracking. The per-record checks live in the controller
// and AssignmentWorkflow service; the gate only requires an active account.
Route::middleware(['auth', 'can:view-assignments'])->group(function () {
    Route::get('/assignments', [WorkAssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/create', [WorkAssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/assignments', [WorkAssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/assignments/{assignment}', [WorkAssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/actions', [WorkAssignmentController::class, 'transition'])->name('assignments.transition');
    Route::post('/assignments/{assignment}/attachments', [WorkAssignmentController::class, 'upload'])->name('assignments.attachments.store');
    Route::get('/assignments/{assignment}/attachments/{attachment}', [WorkAssignmentController::class, 'download'])->name('assignments.attachments.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/unit-desk/{unit}/requests', [UnitServiceRequestController::class, 'index'])->name('unit.service-requests.index');
    Route::get('/registry/requests', [UnitServiceRequestController::class, 'index'])
        ->defaults('unit', 'registry')
        ->name('registry.service-requests.index');
    Route::get('/bursary/requests', [UnitServiceRequestController::class, 'index'])
        ->defaults('unit', 'bursary')
        ->name('bursary.service-requests.index');
    Route::get('/ict/requests', [UnitServiceRequestController::class, 'index'])
        ->defaults('unit', 'ict')
        ->name('ict.service-requests.index');
    Route::post('/unit-desk/requests/{serviceRequest}/status', [UnitServiceRequestController::class, 'updateStatus'])->name('unit.service-requests.update-status');
});

Route::middleware(['auth', 'can:view-reports'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});

Route::middleware(['auth', 'can:access-leave-approvals'])->group(function () {
    Route::get('/approvals/leave', [LeaveApprovalController::class, 'index'])->name('approvals.leave.index');
    Route::post('/approvals/leave/{leave}/action', [LeaveApprovalController::class, 'action'])->name('approvals.leave.action');
});

Route::middleware(['auth', 'can:manage-memos'])->group(function () {
    Route::get('/memos/create', [MemoController::class, 'create'])->name('memos.create');
    Route::post('/memos', [MemoController::class, 'store'])->name('memos.store');
    Route::get('/memos/{memo}/edit', [MemoController::class, 'edit'])->name('memos.edit');
    Route::patch('/memos/{memo}', [MemoController::class, 'update'])->name('memos.update');
    Route::post('/memos/{memo}/publish', [MemoController::class, 'publish'])->name('memos.publish');
    Route::post('/memos/{memo}/withdraw', [MemoController::class, 'withdraw'])->name('memos.withdraw');
});

Route::middleware('auth')->group(function () {
    Route::get('/memos/{memo}', [MemoController::class, 'show'])->name('memos.show');
    Route::post('/memos/{memo}/acknowledge', [MemoController::class, 'acknowledge'])->name('memos.acknowledge');
});

Route::middleware('auth')
    ->prefix('registry')
    ->name('registry.')
    ->group(function () {
        // Registry chain of command (OD-20). The queue is stage-scoped inside
        // the controller, so an officer only ever sees work at their own stage.
        Route::middleware('can:any-registry-approval')->group(function () {
            Route::get('/profile-change-requests', [RegistryProfileChangeRequestController::class, 'index'])
                ->name('profile-change-requests.index');
        });

        Route::middleware('can:any-registry-approval')->group(function () {
            Route::post('/profile-change-requests/{profileChangeRequest}/reject', [RegistryProfileChangeRequestController::class, 'reject'])
                ->name('profile-change-requests.reject');
        });

        Route::middleware('can:head-review-registry')->group(function () {
            Route::post('/profile-change-requests/{profileChangeRequest}/head-review', [RegistryProfileChangeRequestController::class, 'headReview'])
                ->name('profile-change-requests.head-review');
        });

        Route::middleware('can:approve-as-registrar')->group(function () {
            Route::post('/profile-change-requests/{profileChangeRequest}/registrar', [RegistryProfileChangeRequestController::class, 'registrarDecision'])
                ->name('profile-change-requests.registrar');
        });

        Route::middleware('can:escalate-to-director')->group(function () {
            Route::post('/profile-change-requests/{profileChangeRequest}/director', [RegistryProfileChangeRequestController::class, 'directorDecision'])
                ->name('profile-change-requests.director');
        });

        Route::middleware('can:manage-personnel-records')->group(function () {
            Route::get('/personnel/create', [PersonnelDirectoryController::class, 'create'])
                ->name('personnel.create');
            Route::post('/personnel', [PersonnelDirectoryController::class, 'store'])
                ->name('personnel.store');
            Route::get('/personnel', [PersonnelDirectoryController::class, 'index'])
                ->name('personnel.index');
            Route::get('/personnel-imports/create', [PersonnelImportController::class, 'create'])
                ->name('personnel-imports.create');
            Route::get('/personnel-imports/template', [PersonnelImportController::class, 'template'])
                ->name('personnel-imports.template');
            Route::post('/personnel-imports', [PersonnelImportController::class, 'store'])
                ->name('personnel-imports.store');
            Route::get('/personnel-imports/{personnelImportBatch}', [PersonnelImportController::class, 'show'])
                ->name('personnel-imports.show');
            Route::post('/personnel-imports/{personnelImportBatch}/commit', [PersonnelImportController::class, 'commit'])
                ->name('personnel-imports.commit');
        });

        // Registry correspondence: preparation, versioning and approval.
        Route::middleware('can:manage-correspondence')->group(function () {
            Route::get('/correspondence/create', [RegistryCorrespondenceController::class, 'create'])
                ->name('correspondence.create');
            Route::post('/correspondence', [RegistryCorrespondenceController::class, 'store'])
                ->name('correspondence.store');
        });

        Route::prefix('correspondence')->name('correspondence.')->group(function () {
            Route::get('/', [RegistryCorrespondenceController::class, 'index'])->name('index');
            Route::get('/{correspondence}', [RegistryCorrespondenceController::class, 'show'])->name('show');
            Route::patch('/{correspondence}', [RegistryCorrespondenceController::class, 'revise'])->name('revise');
            Route::post('/{correspondence}/submit', [RegistryCorrespondenceController::class, 'submit'])->name('submit');
            Route::post('/{correspondence}/decide', [RegistryCorrespondenceController::class, 'decide'])->name('decide');
            Route::post('/{correspondence}/issue', [RegistryCorrespondenceController::class, 'issue'])
                ->middleware('can:issue-correspondence')
                ->name('issue');
            Route::post('/{correspondence}/withdraw', [RegistryCorrespondenceController::class, 'withdraw'])->name('withdraw');
            Route::post('/{correspondence}/attachments', [RegistryCorrespondenceController::class, 'uploadAttachment'])->middleware('throttle:20,1')->name('attachments.store');
            Route::get('/{correspondence}/attachments/{attachment}', [RegistryCorrespondenceController::class, 'downloadAttachment'])->middleware('throttle:60,1')->name('attachments.download');
            Route::get('/{correspondence}/download', [RegistryCorrespondenceController::class, 'download'])->name('download');
        });

        // Configurable hierarchy: units, reporting lines and delegations.
        Route::middleware('can:manage-organisation')->group(function () {
            Route::get('/organisation', [OrganisationController::class, 'index'])->name('organisation.index');
            Route::post('/organisation/units', [OrganisationController::class, 'storeUnit'])->name('organisation.units.store');
            Route::patch('/organisation/units/{unit}', [OrganisationController::class, 'updateUnit'])->name('organisation.units.update');
            Route::post('/organisation/reporting', [OrganisationController::class, 'storeReporting'])->name('organisation.reporting.store');
            Route::patch('/organisation/reporting/{relationship}/end', [OrganisationController::class, 'endReporting'])->name('organisation.reporting.end');
            Route::post('/organisation/delegations', [OrganisationController::class, 'storeDelegation'])->name('organisation.delegations.store');
            Route::patch('/organisation/delegations/{delegation}/revoke', [OrganisationController::class, 'revokeDelegation'])->name('organisation.delegations.revoke');
        });
    });

Route::middleware(['auth', 'can:manage-payslips'])
    ->prefix('bursary')
    ->name('bursary.')
    ->group(function () {
        Route::get('/payslips', [BursaryPayslipBatchController::class, 'index'])
            ->name('payslips.index');
        Route::get('/payslips/create', [BursaryPayslipBatchController::class, 'create'])
            ->name('payslips.create');
        Route::post('/payslips', [BursaryPayslipBatchController::class, 'store'])
            ->name('payslips.store');
        Route::get('/payslips/{batch}', [BursaryPayslipBatchController::class, 'show'])
            ->name('payslips.show');
        Route::post('/payslips/{batch}/publish', [BursaryPayslipBatchController::class, 'publish'])
            ->middleware('can:release-payslips')
            ->name('payslips.publish');
        Route::post('/payslips/{batch}/withdraw', [BursaryPayslipBatchController::class, 'withdraw'])
            ->name('payslips.withdraw');
        Route::delete('/payslips/{batch}', [BursaryPayslipBatchController::class, 'destroy'])
            ->name('payslips.destroy');
    });

Route::middleware(['auth', 'can:manage-accounts'])
    ->prefix('ict')
    ->name('ict.')
    ->group(function () {
        Route::get('/accounts', [IctAccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts/{user}/status', [IctAccountController::class, 'updateStatus'])->name('accounts.status');
        Route::post('/accounts/{user}/role', [IctAccountController::class, 'updateRole'])->name('accounts.role');
        Route::post('/accounts/{user}/password', [IctAccountController::class, 'issueTemporaryPassword'])->name('accounts.password');
    });

Route::middleware(['auth', 'can:view-audit-log'])->group(function () {
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
});

// Duty assignment is authorised per target account by the `manage-duties` gate,
// so unit leads (DUTY_LEAD, OD-19) can manage colleagues in their own unit while
// ICT / Institutional superadmins (account.manage) manage any account.
Route::middleware(['auth'])->prefix('ict')->name('ict.')->group(function () {
    Route::get('/team-duties', [IctAccountController::class, 'teamDuties'])->name('team-duties.index');
    Route::get('/accounts/{user}/duties', [IctAccountController::class, 'duties'])->name('accounts.duties.index');
    Route::post('/accounts/{user}/duties', [IctAccountController::class, 'grantDuty'])->name('accounts.duties.grant');
    Route::post('/accounts/{user}/duties/revoke', [IctAccountController::class, 'revokeDuty'])->name('accounts.duties.revoke');
});
