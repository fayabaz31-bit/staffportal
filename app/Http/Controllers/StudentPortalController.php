<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentInvoice;
use App\Models\StudentNotice;
use App\Support\RemitaGateway;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StudentPortalController extends Controller
{
    public function login(): View|RedirectResponse
    {
        if (Auth::guard('student')->check()) {
            return redirect()->route('student.dashboard');
        }

        return view('student.auth', ['mode' => 'login']);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']]);
        $identifier = trim($data['identifier']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'student_number';
        $identifier = $field === 'email' ? strtolower($identifier) : strtoupper($identifier);
        if (! Auth::guard('student')->attempt([$field => $identifier, 'password' => $data['password'], 'status' => 'active'])) {
            return back()->withErrors(['identifier' => 'Check your student number or email and password.'])->onlyInput('identifier');
        }
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    public function forgot(): View
    {
        return view('student.auth', ['mode' => 'forgot']);
    }

    public function emailReset(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::broker('students')->sendResetLink($data);

        return back()->with('status', 'If your email has a student account, a reset link has been sent.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('student.auth', ['mode' => 'reset', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function saveReset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(12)]]);
        $status = Password::broker('students')->reset($data, function (Student $student, string $password): void {
            $student->forceFill(['password' => $password, 'remember_token' => null])->save();
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->route('student.login')->with('status', 'Password reset. You can now sign in.');
    }

    public function show(Request $request, string $section = 'dashboard'): View
    {
        $student = $request->user('student');
        $notices = StudentNotice::query()->where('published_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('programme')->orWhere('programme', $student->programme))
            ->where(fn ($query) => $query->whereNull('session')->orWhere('session', $student->session))
            ->orderByDesc('published_at')->get();

        return view('student.portal', [
            'student' => $student, 'section' => $section, 'notices' => $notices,
            'courses' => StudentCourse::query()->where('programme', $student->programme)->where('session', $student->session)->orderBy('code')->get(),
            'enrollments' => $student->enrollments()->with('course')->get(),
            'invoices' => $student->invoices()->latest()->get(),
            'documents' => $student->documents()->latest()->get(),
            'requests' => $student->requests()->with('messages')->latest()->get(),
        ]);
    }

    public function profile(Request $request): RedirectResponse
    {
        $changes = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'], 'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);
        $request->user('student')->requests()->create([
            'category' => 'profile', 'subject' => 'Profile correction', 'body' => 'Please review my proposed biodata changes.',
            'proposed_changes' => $changes,
        ]);

        return back()->with('status', 'Your correction has been sent to Registry for review.');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(['courses' => ['required', 'array', 'min:1'], 'courses.*' => ['required', 'integer', 'distinct']]);
        $student = $request->user('student');
        DB::transaction(function () use ($student, $data): void {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $courses = StudentCourse::query()->whereIn('id', $data['courses'])->where('programme', $student->programme)
                ->where('session', $student->session)->where('registration_open', true)->lockForUpdate()->get();
            abort_unless($courses->count() === count($data['courses']), 422, 'One or more courses are unavailable for your programme or session.');
            foreach ($courses as $course) {
                $student->enrollments()->firstOrCreate(['student_course_id' => $course->id]);
            }
        });

        return back()->with('status', 'Course registration submitted for review.');
    }

    public function requestService(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['accommodation', 'academic', 'finance', 'technical', 'general'])],
            'subject' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:5000'],
        ]);
        $student = $request->user('student');
        DB::transaction(function () use ($student, $data): void {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            if ($data['category'] === 'accommodation') {
                abort_if($student->requests()->where('category', 'accommodation')->whereIn('status', ['open', 'approved'])->exists(), 422, 'An accommodation request is already open or approved.');
            }
            $student->requests()->create($data);
        });

        return back()->with('status', 'Your request has been submitted.');
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $ticket = $request->user('student')->requests()->findOrFail($id);
        abort_unless($ticket->status === 'open', 422, 'This request is closed.');
        $ticket->messages()->create($data);

        return back()->with('status', 'Reply sent.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);
        $file = $request->file('document');
        $path = $file->store('students/'.$request->user('student')->id, 'private');
        abort_unless($path, 503, 'Document storage is unavailable.');
        try {
            $request->user('student')->documents()->create(['title' => $data['title'], 'path' => $path, 'original_name' => $file->getClientOriginalName()]);
        } catch (\Throwable $error) {
            Storage::disk('private')->delete($path);
            throw $error;
        }

        return back()->with('status', 'Document uploaded for verification.');
    }

    public function download(Request $request, int $id): Response
    {
        $document = $request->user('student')->documents()->findOrFail($id);

        return Storage::disk('private')->download($document->path, $document->original_name);
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:student'],
            'password' => ['nullable', 'confirmed', PasswordRule::min(12)],
            'email_notices' => ['sometimes', 'boolean'],
        ]);
        $student = $request->user('student');
        $student->email_notices = $request->boolean('email_notices');
        if (! empty($data['password'])) {
            $student->password = $data['password'];
            $student->remember_token = null;
        }
        $student->save();
        $request->session()->regenerate();

        return back()->with('status', 'Security and notification preferences saved.');
    }

    public function pay(Request $request, int $id, RemitaGateway $gateway): RedirectResponse
    {
        $invoice = $request->user('student')->invoices()->findOrFail($id);
        $gateway->generate($invoice, $request->user('student'));

        return back()->with('status', 'Your Remita reference is ready. Use it to pay through Remita or a bank, then check payment status here.');
    }

    public function verify(Request $request, int $id, RemitaGateway $gateway): RedirectResponse
    {
        $invoice = $request->user('student')->invoices()->findOrFail($id);
        $gateway->verify($invoice);

        return back()->with('status', $invoice->fresh()->paid_at ? 'Payment verified. Your receipt is available.' : 'Remita has not confirmed payment yet.');
    }

    public function receipt(Request $request, int $id): Response
    {
        $student = $request->user('student');
        $invoice = $student->invoices()->findOrFail($id);
        abort_unless($invoice->paid_at, 404);

        return Pdf::loadView('student.receipt', compact('student', 'invoice'))->download('NFLV-receipt-'.$invoice->id.'.pdf');
    }

    public function paymentNotification(Request $request, RemitaGateway $gateway): JsonResponse
    {
        $data = $request->validate(['rrr' => ['required', 'string', 'regex:/^[0-9]{6,20}$/']]);
        $invoice = StudentInvoice::query()->where('rrr', $data['rrr'])->first();
        if ($invoice) {
            $gateway->verify($invoice);
        }

        return response()->json(['received' => true]);
    }

    public function results(Request $request): Response
    {
        $student = $request->user('student');
        $enrollments = $student->enrollments()->with('course')->whereNotNull('result_published_at')->where('result_published_at', '<=', now())->get();
        abort_if($enrollments->isEmpty(), 404);

        return Pdf::loadView('student.statement', compact('student', 'enrollments'))->download('NFLV-results.pdf');
    }
}
