<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Duty;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentNotice;
use App\Models\User;
use App\Notifications\StudentResetPassword;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sign_in_uses_a_separate_guard_and_inactive_accounts_are_rejected(): void
    {
        $student = Student::factory()->create();
        $this->get('/student/dashboard')->assertRedirect('/student');
        $this->post('/student/login', ['identifier' => $student->student_number, 'password' => 'Student-test-2026'])->assertRedirect('/student/dashboard');
        $this->assertAuthenticatedAs($student, 'student');
        $this->assertGuest('web');
        $student->update(['status' => 'inactive']);
        $this->get('/student/dashboard')->assertRedirect('/student');
        $this->post('/student/login', ['identifier' => $student->email, 'password' => 'Student-test-2026'])->assertSessionHasErrors('identifier');
    }

    public function test_all_student_pages_render_with_real_records_and_no_other_students_data(): void
    {
        $student = Student::factory()->create();
        StudentNotice::query()->create(['title' => 'Other programme secret', 'body' => 'Private', 'programme' => 'Other programme', 'published_at' => now()]);
        StudentNotice::query()->create(['title' => 'Future notice secret', 'body' => 'Private', 'published_at' => now()->addDay()]);
        foreach (['dashboard', 'profile', 'registration', 'timetable', 'results', 'fees', 'documents', 'accommodation', 'notices', 'support', 'settings'] as $section) {
            $this->actingAs($student, 'student')->get('/student/'.$section)->assertOk()->assertDontSee('Other programme secret')->assertDontSee('Future notice secret');
        }
        $this->actingAs($student, 'student')->get('/student/nonexistent')->assertNotFound();
    }

    public function test_course_registration_is_scoped_idempotent_and_respects_registration_windows(): void
    {
        $student = Student::factory()->create();
        $course = StudentCourse::query()->create(['code' => 'FRE101', 'title' => 'Oral French', 'programme' => $student->programme, 'session' => $student->session, 'registration_open' => true]);
        $this->actingAs($student, 'student')->post('/student/registration', ['courses' => [$course->id]])->assertRedirect();
        $this->post('/student/registration', ['courses' => [$course->id]])->assertRedirect();
        $this->assertDatabaseCount('student_enrollments', 1);
        $course->update(['registration_open' => false]);
        $this->post('/student/registration', ['courses' => [$course->id]])->assertUnprocessable();
        $course->update(['registration_open' => true, 'programme' => 'Unrelated']);
        $this->post('/student/registration', ['courses' => [$course->id]])->assertUnprocessable();
    }

    public function test_profile_corrections_require_staff_approval_and_cannot_change_programme(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student, 'student')->post('/student/profile', ['name' => 'Corrected Name', 'phone' => '08012345678', 'programme' => 'Changed'])->assertRedirect();
        $this->assertNotSame('Corrected Name', $student->fresh()->name);
        $ticket = $student->requests()->firstOrFail();
        $registry = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->actingAs($registry, 'web')->post(route('students.admin.review', [$student, $ticket->id]), ['status' => 'approved', 'decision_note' => 'Checked registration documents'])->assertRedirect();
        $this->assertSame('Corrected Name', $student->fresh()->name);
        $this->assertSame('Language immersion', $student->fresh()->programme);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.request.reviewed']);
    }

    public function test_private_documents_and_finance_records_cannot_be_read_by_another_student(): void
    {
        Storage::fake('private');
        $owner = Student::factory()->create();
        $other = Student::factory()->create();
        $this->actingAs($owner, 'student')->post('/student/documents', ['title' => 'Credentials', 'document' => UploadedFile::fake()->create('credentials.pdf', 40, 'application/pdf')])->assertRedirect();
        $document = $owner->documents()->firstOrFail();
        Storage::disk('private')->assertExists($document->path);
        $this->get('/student/documents/'.$document->id)->assertOk();
        $invoice = $owner->invoices()->create(['reference' => Str::uuid(), 'description' => 'Tuition', 'amount_kobo' => 150000, 'session' => '2026/2027']);
        $this->actingAs($other, 'student')->get('/student/documents/'.$document->id)->assertNotFound();
        $this->post('/student/fees/'.$invoice->id.'/pay')->assertNotFound();
        $this->get('/student/fees/'.$invoice->id.'/receipt')->assertNotFound();
        $this->post('/student/documents', ['title' => 'Unsafe', 'document' => UploadedFile::fake()->create('page.html', 5, 'text/html')])->assertSessionHasErrors('document');
    }

    public function test_unpublished_results_are_hidden_and_approved_results_can_be_downloaded(): void
    {
        $student = Student::factory()->create();
        $course = StudentCourse::query()->create(['code' => 'FRE201', 'title' => 'French study', 'programme' => $student->programme, 'session' => $student->session]);
        $enrollment = $student->enrollments()->create(['student_course_id' => $course->id, 'status' => 'approved', 'grade' => 'DISTINCTION', 'score' => 84]);
        $this->actingAs($student, 'student')->get('/student/results')->assertDontSee('DISTINCTION');
        $this->get('/student/results/statement')->assertNotFound();
        $enrollment->update(['result_published_at' => now()]);
        $this->get('/student/results')->assertSee('DISTINCTION');
        $this->get('/student/results/statement')->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_staff_permissions_prevent_ordinary_staff_from_accessing_student_administration(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $student = Student::factory()->create();
        $this->actingAs($staff)->get(route('students.admin.index'))->assertForbidden();
        $registry = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->actingAs($registry)->get(route('students.admin.show', $student))->assertOk();
        $this->post(route('students.admin.invoice', $student), ['description' => 'Fee', 'amount_kobo' => 1000, 'session' => '2026/2027'])->assertForbidden();
    }

    public function test_requests_and_accommodation_are_owned_and_duplicate_allocations_are_prevented(): void
    {
        $student = Student::factory()->create();
        $other = Student::factory()->create();
        $this->actingAs($student, 'student')->post('/student/requests', ['category' => 'accommodation', 'subject' => 'Hostel request', 'body' => 'I need a room.'])->assertRedirect();
        $ticket = $student->requests()->firstOrFail();
        $this->post('/student/requests', ['category' => 'accommodation', 'subject' => 'Second request', 'body' => 'Another room'])->assertUnprocessable();
        $this->actingAs($other, 'student')->post('/student/requests/'.$ticket->id.'/reply', ['body' => 'Not mine'])->assertNotFound();
    }

    public function test_remita_must_verify_the_amount_and_reference_before_issuing_a_receipt(): void
    {
        config(['student.remita.merchant_id' => '123', 'student.remita.service_type_id' => '456', 'student.remita.api_key' => 'test-key', 'student.remita.base_url' => 'https://remita.test']);
        Http::preventStrayRequests();
        $student = Student::factory()->create();
        $invoice = $student->invoices()->create(['reference' => Str::uuid(), 'description' => 'Tuition', 'amount_kobo' => 150000, 'session' => '2026/2027', 'rrr' => '123456789012']);
        Http::fake(['*' => Http::sequence()
            ->push(['status' => '00', 'RRR' => $invoice->rrr, 'orderId' => $invoice->reference, 'amount' => '1.00'])
            ->push(['status' => '00', 'RRR' => $invoice->rrr, 'orderId' => $invoice->reference, 'amount' => '1500.00'])]);
        $this->actingAs($student, 'student')->post('/student/fees/'.$invoice->id.'/verify')->assertSessionHasErrors('payment');
        $this->assertNull($invoice->fresh()->paid_at);
        $this->post('/student/fees/'.$invoice->id.'/verify')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->get('/student/fees/'.$invoice->id.'/receipt')->assertOk()->assertHeader('content-type', 'application/pdf');
        Http::assertSentCount(2);
    }

    public function test_remita_reference_uses_the_existing_bill_and_is_not_generated_twice(): void
    {
        config(['student.remita.merchant_id' => '123', 'student.remita.service_type_id' => '456', 'student.remita.api_key' => 'test-key', 'student.remita.base_url' => 'https://remita.test']);
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['statuscode' => '025', 'RRR' => '123456789012'])]);
        $student = Student::factory()->create();
        $invoice = $student->invoices()->create(['reference' => Str::uuid(), 'description' => 'Tuition', 'amount_kobo' => 150000, 'session' => '2026/2027']);
        $this->actingAs($student, 'student')->post('/student/fees/'.$invoice->id.'/pay', ['amount' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/student/fees/'.$invoice->id.'/pay')->assertRedirect();
        Http::assertSent(fn ($request) => $request['amount'] === '1500.00' && $request['orderId'] === (string) $invoice->reference);
        Http::assertSentCount(1);
        $this->assertSame('123456789012', $invoice->fresh()->rrr);
    }

    public function test_password_recovery_is_separate_from_staff_accounts(): void
    {
        Notification::fake();
        $student = Student::factory()->create();
        $this->post('/student/forgot-password', ['email' => $student->email])->assertRedirect();
        Notification::assertSentTo($student, StudentResetPassword::class, function ($notification) use ($student): bool {
            $this->post('/student/reset-password', ['email' => $student->email, 'token' => $notification->token, 'password' => 'A-new-student-password-2026', 'password_confirmation' => 'A-new-student-password-2026'])->assertRedirect('/student');

            return true;
        });
        $this->post('/student/login', ['identifier' => $student->email, 'password' => 'A-new-student-password-2026'])->assertRedirect('/student/dashboard');
    }

    public function test_registry_can_create_an_account_publish_courses_and_issue_a_result(): void
    {
        $registry = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->actingAs($registry)->post(route('students.admin.create'), [
            'name' => 'New Student', 'student_number' => 'NFLV/001', 'email' => 'new.student@example.test',
            'programme' => 'Immersion', 'session' => '2026/2027', 'password' => 'A-new-student-password-2026', 'password_confirmation' => 'A-new-student-password-2026',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $student = Student::query()->where('student_number', 'NFLV/001')->firstOrFail();
        $this->post(route('students.admin.courses'), [
            'code' => 'FRE001', 'title' => 'Conversation', 'programme' => 'Immersion', 'session' => '2026/2027',
            'credits' => 2, 'timetable' => 'Monday 10:00 Room 1', 'registration_open' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $course = StudentCourse::query()->where('code', 'FRE001')->firstOrFail();
        $enrollment = $student->enrollments()->create(['student_course_id' => $course->id]);
        $this->post(route('students.admin.enrollment', [$student, $enrollment->id]), [
            'status' => 'approved', 'score' => 75, 'grade' => 'A', 'publish' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($enrollment->fresh()->result_published_at);
        $this->get(route('students.admin.index'))->assertOk()->assertSee('New Student');
    }

    public function test_bursary_access_is_limited_to_billing_and_finance_enquiries(): void
    {
        $bursary = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $duty = Duty::query()->firstOrCreate(['code' => DutyCatalog::BURSARY_RECONCILIATION], ['label' => 'Finance reconciliation', 'unit' => 'bursary', 'is_active' => true]);
        $bursary->duties()->syncWithoutDetaching([$duty->id]);
        $student = Student::factory()->create();
        $private = $student->requests()->create(['category' => 'profile', 'subject' => 'Private biodata correction', 'body' => 'Private']);
        $this->actingAs($bursary)->get(route('students.admin.show', $student))->assertOk()->assertDontSee('Private biodata correction');
        $this->post(route('students.admin.reply', [$student, $private->id]), ['body' => 'Unauthorised'])->assertForbidden();
        $bill = ['reference' => (string) Str::uuid(), 'description' => 'Tuition', 'amount_kobo' => 250000, 'session' => $student->session];
        $this->post(route('students.admin.invoice', $student), $bill)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('students.admin.invoice', $student), $bill)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_invoices', 1);
        $this->assertDatabaseHas('student_invoices', ['student_id' => $student->id, 'amount_kobo' => 250000]);
    }

    public function test_payment_notifications_ignore_unverified_claims_and_reconcile_server_verified_payments(): void
    {
        config(['student.remita.merchant_id' => '123', 'student.remita.service_type_id' => '456', 'student.remita.api_key' => 'test-key', 'student.remita.base_url' => 'https://remita.test']);
        Http::preventStrayRequests();
        $student = Student::factory()->create();
        $invoice = $student->invoices()->create(['reference' => (string) Str::uuid(), 'description' => 'Tuition', 'amount_kobo' => 150000, 'session' => '2026/2027', 'rrr' => '123456789012']);
        Http::fake(['*' => Http::sequence()
            ->push(['status' => '021'])
            ->push(['status' => '00', 'RRR' => $invoice->rrr, 'orderId' => $invoice->reference, 'amount' => '1500.00'])]);
        $this->postJson('/student/payments/remita/notify', ['rrr' => $invoice->rrr, 'status' => '00'])->assertOk();
        $this->assertNull($invoice->fresh()->paid_at);
        $this->postJson('/student/payments/remita/notify', ['rrr' => $invoice->rrr])->assertOk();
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->postJson('/student/payments/remita/notify', ['rrr' => $invoice->rrr])->assertOk();
        Http::assertSentCount(2);
    }
}
