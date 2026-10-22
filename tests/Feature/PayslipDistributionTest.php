<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Department;
use App\Models\Payslip;
use App\Models\PayslipBatch;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PayslipDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * A Bursary officer authorised to release batches (upload and release are
     * separated by policy, so this is never the uploader).
     */
    private function releaseApprover(): User
    {
        $approver = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        $duty = DutyCatalog::ensureDuty(DutyCatalog::BURSARY_PAYSLIP_RELEASE);
        $approver->duties()->syncWithoutDetaching([$duty->getKey() => ['assigned_at' => now()]]);
        $approver->unsetRelation('duties');

        return $approver;
    }

    public function test_staff_can_view_own_published_payslips_index(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::Staff,
            'staff_number' => 'NFLV-0001',
        ]);
        StaffProfile::factory()->for($staff)->create();

        $batch = PayslipBatch::factory()->create([
            'payroll_period' => '2026-09',
            'status' => PayslipBatch::STATUS_PUBLISHED,
        ]);

        $payslip = Payslip::factory()->create([
            'payslip_batch_id' => $batch->id,
            'user_id' => $staff->id,
            'staff_number' => 'NFLV-0001',
            'payroll_period' => '2026-09',
            'status' => Payslip::STATUS_PUBLISHED,
        ]);

        $response = $this->actingAs($staff)->get(route('payslips.index'));

        $response->assertOk()
            ->assertSeeText('My Payslips')
            ->assertSeeText('September 2026')
            ->assertSee(route('payslips.download', $payslip));
    }

    public function test_staff_can_download_own_published_payslip(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::Staff,
            'staff_number' => 'NFLV-0001',
        ]);
        StaffProfile::factory()->for($staff)->create();

        $storagePath = 'payslips/2026-09/test_payslip.pdf';
        Storage::disk('local')->put($storagePath, "%PDF-1.4\nTest PDF content");

        $payslip = Payslip::factory()->create([
            'user_id' => $staff->id,
            'staff_number' => 'NFLV-0001',
            'payroll_period' => '2026-09',
            'storage_path' => $storagePath,
            'status' => Payslip::STATUS_PUBLISHED,
            'download_count' => 0,
        ]);

        $response = $this->actingAs($staff)->get(route('payslips.download', $payslip));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Payslip_2026-09_NFLV-0001.pdf', (string) $response->headers->get('Content-Disposition'));

        $payslip->refresh();
        $this->assertSame(1, $payslip->download_count);
        $this->assertNotNull($payslip->last_downloaded_at);
    }

    public function test_ac03_staff_a_cannot_download_staff_b_payslip(): void
    {
        $staffA = User::factory()->create([
            'role' => UserRole::Staff,
            'staff_number' => 'NFLV-0001',
        ]);
        $staffB = User::factory()->create([
            'role' => UserRole::Staff,
            'staff_number' => 'NFLV-0002',
        ]);

        $storagePath = 'payslips/2026-09/staff_b_payslip.pdf';
        Storage::disk('local')->put($storagePath, "%PDF-1.4\nConfidential salary of Staff B");

        $payslipB = Payslip::factory()->create([
            'user_id' => $staffB->id,
            'staff_number' => 'NFLV-0002',
            'payroll_period' => '2026-09',
            'storage_path' => $storagePath,
            'status' => Payslip::STATUS_PUBLISHED,
        ]);

        // Staff A attempts to download Staff B's payslip
        $response = $this->actingAs($staffA)->get(route('payslips.download', $payslipB));

        // Must be rejected with 404 to avoid information disclosure
        $response->assertNotFound();
    }

    public function test_registry_admin_cannot_access_bursary_payslip_management(): void
    {
        $registryAdmin = User::factory()->create([
            'role' => UserRole::HrRegistryAdmin,
        ]);

        $response = $this->actingAs($registryAdmin)->get(route('bursary.payslips.index'));

        $response->assertForbidden();
    }

    public function test_ict_admin_cannot_access_bursary_payslip_management(): void
    {
        $ictAdmin = User::factory()->create([
            'role' => UserRole::IctSuperadmin,
        ]);

        $response = $this->actingAs($ictAdmin)->get(route('bursary.payslips.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_payslip_routes(): void
    {
        $this->get(route('payslips.index'))->assertRedirectToRoute('login');
        $this->get(route('bursary.payslips.index'))->assertRedirectToRoute('login');
    }

    public function test_bursary_admin_can_stage_a_valid_payslip_batch_via_zip(): void
    {
        $bursaryAdmin = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $staff = User::factory()->create(['staff_number' => 'NFLV-0001', 'status' => 'active']);
        $dept = Department::factory()->create(['name' => 'Bursary']);
        StaffProfile::factory()->for($staff)->for($dept)->create();

        $zipFile = $this->createZipWithFiles([
            'NFLV-0001.pdf' => "%PDF-1.4\nValid PDF content for NFLV-0001",
        ]);

        $response = $this->actingAs($bursaryAdmin)->post(route('bursary.payslips.store'), [
            'payroll_period' => '2026-09',
            'batch_file' => $zipFile,
        ]);

        $response->assertRedirect();
        $batch = PayslipBatch::query()->firstOrFail();

        $this->assertSame('2026-09', $batch->payroll_period);
        $this->assertSame(PayslipBatch::STATUS_STAGED, $batch->status);
        $this->assertSame(1, $batch->total_files);
        $this->assertSame(1, $batch->matched_files);
        $this->assertSame(0, $batch->error_count);
    }

    public function test_ac05_batch_with_mismatched_staff_number_is_quarantined_and_blocked(): void
    {
        $bursaryAdmin = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        // Upload ZIP with unknown staff number
        $zipFile = $this->createZipWithFiles([
            'NFLV-9999.pdf' => "%PDF-1.4\nUnknown staff payslip content",
        ]);

        $response = $this->actingAs($bursaryAdmin)->post(route('bursary.payslips.store'), [
            'payroll_period' => '2026-09',
            'batch_file' => $zipFile,
        ]);

        $response->assertRedirect();
        $batch = PayslipBatch::query()->firstOrFail();

        $this->assertSame(PayslipBatch::STATUS_QUARANTINED, $batch->status);
        $this->assertSame(1, $batch->error_count);
        $this->assertSame(0, $batch->matched_files);

        // Attempting to publish a quarantined batch must be blocked
        $approver = $this->releaseApprover();
        $publishResponse = $this->actingAs($approver)->post(route('bursary.payslips.publish', $batch));
        $publishResponse->assertSessionHas('error');
        $this->assertDatabaseCount('payslips', 0);
    }

    public function test_batch_with_corrupt_non_pdf_file_is_quarantined(): void
    {
        $bursaryAdmin = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        User::factory()->create(['staff_number' => 'NFLV-0001', 'status' => 'active']);

        $zipFile = $this->createZipWithFiles([
            'NFLV-0001.pdf' => 'THIS IS NOT A PDF FILE (MISSING HEADER)',
        ]);

        $response = $this->actingAs($bursaryAdmin)->post(route('bursary.payslips.store'), [
            'payroll_period' => '2026-09',
            'batch_file' => $zipFile,
        ]);

        $response->assertRedirect();
        $batch = PayslipBatch::query()->firstOrFail();

        $this->assertSame(PayslipBatch::STATUS_QUARANTINED, $batch->status);
        $this->assertSame(1, $batch->error_count);
    }

    public function test_bursary_admin_can_approve_and_publish_staged_batch(): void
    {
        $uploader = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $approver = $this->releaseApprover();
        $staff = User::factory()->create(['staff_number' => 'NFLV-0001', 'status' => 'active']);
        StaffProfile::factory()->for($staff)->create();

        $zipFile = $this->createZipWithFiles([
            'NFLV-0001.pdf' => "%PDF-1.4\nReal monthly salary statement",
        ]);

        $this->actingAs($uploader)->post(route('bursary.payslips.store'), [
            'payroll_period' => '2026-09',
            'batch_file' => $zipFile,
        ]);

        $batch = PayslipBatch::query()->firstOrFail();
        $this->assertSame(PayslipBatch::STATUS_STAGED, $batch->status);

        // Separation of duties: a different authorised officer releases the batch.
        $publishResponse = $this->actingAs($approver)->post(route('bursary.payslips.publish', $batch));
        $publishResponse->assertRedirectToRoute('bursary.payslips.show', $batch);

        $batch->refresh();
        $this->assertSame(PayslipBatch::STATUS_PUBLISHED, $batch->status);
        $this->assertNotNull($batch->published_at);
        $this->assertSame($approver->id, $batch->published_by);

        $this->assertDatabaseHas('payslips', [
            'payslip_batch_id' => $batch->id,
            'user_id' => $staff->id,
            'staff_number' => 'NFLV-0001',
            'payroll_period' => '2026-09',
            'status' => Payslip::STATUS_PUBLISHED,
        ]);

        // Staff can now download their published payslip
        $payslip = Payslip::query()->where('user_id', $staff->id)->firstOrFail();
        $downloadResponse = $this->actingAs($staff)->get(route('payslips.download', $payslip));
        $downloadResponse->assertOk();
    }

    public function test_withdrawn_batch_immediately_revokes_staff_download_access(): void
    {
        $bursaryAdmin = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $approver = $this->releaseApprover();
        $staff = User::factory()->create(['staff_number' => 'NFLV-0001', 'status' => 'active']);
        StaffProfile::factory()->for($staff)->create();

        $zipFile = $this->createZipWithFiles([
            'NFLV-0001.pdf' => "%PDF-1.4\nContent to be withdrawn",
        ]);

        $this->actingAs($bursaryAdmin)->post(route('bursary.payslips.store'), [
            'payroll_period' => '2026-09',
            'batch_file' => $zipFile,
        ]);

        $batch = PayslipBatch::query()->firstOrFail();
        $this->actingAs($approver)->post(route('bursary.payslips.publish', $batch));

        $payslip = Payslip::query()->where('user_id', $staff->id)->firstOrFail();
        $this->actingAs($staff)->get(route('payslips.download', $payslip))->assertOk();

        // Bursary admin withdraws the batch
        $withdrawResponse = $this->actingAs($bursaryAdmin)->post(route('bursary.payslips.withdraw', $batch), [
            'withdrawal_reason' => 'Calculations recalculated due to tax revision.',
        ]);

        $withdrawResponse->assertRedirectToRoute('bursary.payslips.show', $batch);

        $batch->refresh();
        $payslip->refresh();
        $this->assertSame(PayslipBatch::STATUS_WITHDRAWN, $batch->status);
        $this->assertSame(Payslip::STATUS_WITHDRAWN, $payslip->status);

        // Staff download is now denied
        $this->actingAs($staff)->get(route('payslips.download', $payslip))->assertNotFound();
    }

    /**
     * @param  array<string, string>  $files  Map of filename => content
     */
    private function createZipWithFiles(array $files): UploadedFile
    {
        $tmpDir = sys_get_temp_dir();
        $zipPath = $tmpDir.'/test_payslips_'.uniqid().'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return new UploadedFile(
            $zipPath,
            basename($zipPath),
            'application/zip',
            null,
            true
        );
    }
}
