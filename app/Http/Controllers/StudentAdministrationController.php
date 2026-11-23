<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentInvoice;
use App\Models\StudentNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StudentAdministrationController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.administration', [
            'students' => Student::query()->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search
                ->where('name', 'like', '%'.$request->string('search').'%')->orWhere('student_number', 'like', '%'.$request->string('search').'%')))
                ->orderBy('name')->paginate(30)->withQueryString(),
            'courses' => StudentCourse::query()->orderByDesc('id')->limit(100)->get(),
            'student' => null,
        ]);
    }

    public function show(Student $student): View
    {
        return view('student.administration', [
            'students' => null, 'student' => $student,
            'enrollments' => Gate::allows('manage-students') ? $student->enrollments()->with('course')->get() : collect(),
            'invoices' => Gate::allows('manage-student-finance') ? $student->invoices()->latest()->get() : collect(),
            'documents' => Gate::allows('manage-students') ? $student->documents()->latest()->get() : collect(),
            'requests' => $student->requests()->when(! Gate::allows('manage-students'), fn ($query) => $query->where('category', 'finance'))->with('messages')->latest()->get(),
        ]);
    }

    public function createStudent(Request $request): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'student_number' => ['required', 'string', 'max:80', 'unique:students'],
            'email' => ['required', 'email', 'max:255', 'unique:students'],
            'programme' => ['required', 'string', 'max:255'], 'session' => ['required', 'string', 'max:80'],
            'cohort' => ['nullable', 'string', 'max:255'], 'institution' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);
        $data['email'] = strtolower($data['email']);
        $data['student_number'] = strtoupper($data['student_number']);
        $student = DB::transaction(function () use ($data): Student {
            $student = Student::query()->create($data);
            AuditLog::record('student.account.created', $student);

            return $student;
        });

        return redirect()->route('students.admin.show', $student)->with('status', 'Student account created.');
    }

    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'placement_result' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($student, $data): void {
            $student->update($data);
            AuditLog::record('student.account.updated', $student);
        });

        return back()->with('status', 'Account and placement updated.');
    }

    public function course(Request $request): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'], 'title' => ['required', 'string', 'max:255'],
            'programme' => ['required', 'string', 'max:255'], 'session' => ['required', 'string', 'max:80'],
            'credits' => ['required', 'integer', 'between:1,30'], 'timetable' => ['nullable', 'string', 'max:255'],
            'registration_open' => ['sometimes', 'boolean'],
        ]);
        $data['registration_open'] = $request->boolean('registration_open');
        DB::transaction(function () use ($data): void {
            $course = StudentCourse::query()->updateOrCreate(
                ['code' => $data['code'], 'programme' => $data['programme'], 'session' => $data['session']], $data);
            AuditLog::record('student.course.saved', $course);
        });

        return back()->with('status', 'Course saved. Use the same code, programme and session to update its schedule or registration window.');
    }

    public function enrollment(Request $request, Student $student, int $id): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate([
            'status' => ['required', Rule::in(['submitted', 'approved', 'rejected'])],
            'score' => ['nullable', 'numeric', 'between:0,100'], 'grade' => ['nullable', 'string', 'max:10'],
            'publish' => ['sometimes', 'boolean'],
        ]);
        if ($request->boolean('publish')) {
            $request->validate(['score' => ['required'], 'grade' => ['required'], 'status' => [Rule::in(['approved'])]]);
        }
        DB::transaction(function () use ($student, $id, $data, $request): void {
            $enrollment = $student->enrollments()->lockForUpdate()->findOrFail($id);
            $enrollment->update([
                'status' => $data['status'], 'score' => $data['score'], 'grade' => $data['grade'],
                'result_published_at' => $request->boolean('publish') ? now() : null,
            ]);
            AuditLog::record('student.result.saved', $enrollment, ['published' => $request->boolean('publish')]);
        });

        return back()->with('status', 'Registration and result saved.');
    }

    public function invoice(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('manage-student-finance');
        $data = $request->validate([
            'reference' => ['required', 'uuid'],
            'description' => ['required', 'string', 'max:255'], 'amount_kobo' => ['required', 'integer', 'between:1,10000000000'],
            'session' => ['required', 'string', 'max:80'], 'due_date' => ['nullable', 'date'],
        ]);
        DB::transaction(function () use ($student, $data): void {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $invoice = StudentInvoice::query()->firstOrCreate(['reference' => $data['reference']], [...$data, 'student_id' => $student->id]);
            abort_unless($invoice->student_id === $student->id, 422, 'This bill reference belongs to another student.');
            if ($invoice->wasRecentlyCreated) {
                AuditLog::record('student.invoice.issued', $invoice);
            }
        });

        return back()->with('status', 'Bill issued to the student ledger.');
    }

    public function notice(Request $request): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:10000'],
            'programme' => ['nullable', 'string', 'max:255'], 'session' => ['nullable', 'string', 'max:80'],
            'event_date' => ['nullable', 'date'],
        ]);
        DB::transaction(function () use ($data): void {
            $notice = StudentNotice::query()->create([...$data, 'published_at' => now()]);
            AuditLog::record('student.notice.published', $notice);
        });

        return back()->with('status', 'Notice published to the selected student audience.');
    }

    public function review(Request $request, Student $student, int $id): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'resolved'])],
            'decision_note' => ['required', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($student, $id, $data, $request): void {
            $ticket = $student->requests()->lockForUpdate()->findOrFail($id);
            abort_unless($ticket->status === 'open', 422, 'This request has already been decided.');
            if ($ticket->category === 'profile' && $data['status'] === 'approved') {
                $student->update(Arr::only($ticket->proposed_changes ?? [], ['name', 'phone', 'address', 'date_of_birth']));
            }
            if (in_array($ticket->category, ['profile', 'accommodation'], true)) {
                abort_if($data['status'] === 'resolved', 422, 'Approve or reject this request explicitly.');
            }
            $ticket->update([...$data, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            AuditLog::record('student.request.reviewed', $ticket, ['status' => $data['status']]);
        });

        return back()->with('status', 'Decision saved and shared with the student.');
    }

    public function reply(Request $request, Student $student, int $id): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $ticket = $student->requests()->findOrFail($id);
        abort_unless(Gate::allows('manage-students') || $ticket->category === 'finance', 403);
        $ticket->messages()->create([...$data, 'staff_id' => $request->user()->id, 'from_staff' => true]);
        AuditLog::record('student.request.replied', $ticket);

        return back()->with('status', 'Reply sent.');
    }

    public function issueDocument(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'document' => ['required', 'file', 'mimes:pdf', 'max:5120']]);
        $file = $request->file('document');
        $path = $file->store('students/'.$student->id, 'private');
        abort_unless($path, 503, 'Document storage is unavailable.');
        try {
            DB::transaction(function () use ($student, $file, $path, $data): void {
                $document = $student->documents()->create(['title' => $data['title'], 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'issued_by_staff' => true, 'status' => 'issued']);
                AuditLog::record('student.document.issued', $document);
            });
        } catch (\Throwable $error) {
            Storage::disk('private')->delete($path);
            throw $error;
        }

        return back()->with('status', 'Document issued.');
    }

    public function download(Student $student, int $id): Response
    {
        Gate::authorize('manage-students');
        $document = $student->documents()->findOrFail($id);

        return Storage::disk('private')->download($document->path, $document->original_name);
    }

    public function verifyDocument(Request $request, Student $student, int $id): RedirectResponse
    {
        Gate::authorize('manage-students');
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'rejected'])]]);
        $document = $student->documents()->findOrFail($id);
        abort_if($document->issued_by_staff, 422);
        DB::transaction(function () use ($document, $data): void {
            $document->update($data);
            AuditLog::record('student.document.reviewed', $document);
        });

        return back()->with('status', 'Document review saved.');
    }
}
