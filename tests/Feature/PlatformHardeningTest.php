<?php

namespace Tests\Feature;

use App\Models\ReferenceSequence;
use App\Models\ServiceRequest;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Regression coverage for platform-level defects discovered during the Phase 7
 * hardening review: private document vault configuration, the ICT service desk
 * authorisation gate, Zip-Slip safe extraction, atomic reference generation and
 * the institutional business timezone.
 */
class PlatformHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_document_vault_disk_is_configured_and_writable(): void
    {
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.private.root'));
        $this->assertSame('local', config('filesystems.disks.private.driver'));

        Storage::disk('private')->put('hardening-probe.txt', 'private-vault-ok');

        $this->assertSame('private-vault-ok', Storage::disk('private')->get('hardening-probe.txt'));

        Storage::disk('private')->delete('hardening-probe.txt');
    }

    public function test_institutional_business_timezone_is_africa_lagos(): void
    {
        $this->assertSame('Africa/Lagos', config('app.timezone'));
    }

    public function test_ict_service_desk_is_accessible_to_ict_and_institutional_superadmins_only(): void
    {
        $ictAdmin = User::factory()->create([
            'role' => UserRole::IctSuperadmin,
            'status' => 'active',
        ]);
        StaffProfile::factory()->for($ictAdmin)->create();

        $institutional = User::factory()->create([
            'role' => UserRole::InstitutionalSuperadmin,
            'status' => 'active',
        ]);
        $hrAdmin = User::factory()->create([
            'role' => UserRole::HrRegistryAdmin,
            'status' => 'active',
        ]);
        $staff = User::factory()->create([
            'role' => UserRole::Staff,
            'status' => 'active',
        ]);

        $this->actingAs($ictAdmin)->get(route('ict.service-requests.index'))->assertOk();
        $this->actingAs($institutional)->get(route('ict.service-requests.index'))->assertOk();
        $this->actingAs($hrAdmin)->get(route('ict.service-requests.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('ict.service-requests.index'))->assertForbidden();
    }

    public function test_zip_archive_with_path_traversal_entry_is_rejected(): void
    {
        $bursaryAdmin = User::factory()->create([
            'role' => UserRole::BursaryAdmin,
            'status' => 'active',
        ]);
        User::factory()->create(['staff_number' => 'NFLV-0001', 'status' => 'active']);

        $maliciousZip = $this->createZipWithFiles([
            '../outside_the_vault.pdf' => "%PDF-1.4\npath traversal attempt",
        ]);

        $response = $this->actingAs($bursaryAdmin)->post(route('bursary.payslips.store'), [
            'payroll_period' => '2026-09',
            'batch_file' => $maliciousZip,
        ]);

        $response->assertSessionHasErrors('batch_file');
        $this->assertDatabaseCount('payslip_batches', 0);

        // The traversal target must never be written outside the staging vault.
        $this->assertFileDoesNotExist(storage_path('app/outside_the_vault.pdf'));
        $this->assertFileDoesNotExist(base_path('outside_the_vault.pdf'));
    }

    public function test_reference_sequence_increments_atomically_per_scope_and_year(): void
    {
        $this->assertSame(1, ReferenceSequence::next('hardening_test', 2026));
        $this->assertSame(2, ReferenceSequence::next('hardening_test', 2026));
        $this->assertSame(1, ReferenceSequence::next('hardening_test', 2027));

        // A floor honours already-issued legacy references.
        $this->assertSame(6, ReferenceSequence::next('hardening_floored', 2026, 5));
        $this->assertSame(7, ReferenceSequence::next('hardening_floored', 2026));
    }

    public function test_service_requests_receive_unique_tracking_references(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::Staff,
            'status' => 'active',
            'staff_number' => 'NFLV-0500',
        ]);
        StaffProfile::factory()->for($staff)->create();

        foreach (['First enquiry', 'Second enquiry'] as $subject) {
            $this->actingAs($staff)->post(route('service-requests.store'), [
                'unit' => 'registry',
                'category' => 'general_registry',
                'subject' => $subject,
                'description' => 'A sufficiently descriptive enquiry for reference generation.',
                'priority' => 'standard',
            ])->assertRedirect();
        }

        $references = ServiceRequest::query()->pluck('tracking_reference')->all();

        $this->assertCount(2, $references);
        $this->assertCount(2, array_unique($references));
        $this->assertSame('REQ-2026-0002', max($references));
    }

    /**
     * @param  array<string, string>  $files  Map of entry name => content
     */
    private function createZipWithFiles(array $files): UploadedFile
    {
        $zipPath = sys_get_temp_dir().'/hardening_'.uniqid().'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return new UploadedFile($zipPath, basename($zipPath), 'application/zip', null, true);
    }
}
