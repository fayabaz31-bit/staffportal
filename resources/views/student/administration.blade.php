@extends('student.layout', ['administration' => true])
@php $heading = $student ? $student->name : 'Student administration';
@endphp
@section('student-content')
    <header class="sp-title">
        <p class="sp-eyebrow">STAFF WORKSPACE</p>
        <h1>{{ $heading }}</h1>
        <p>Manage student services within your assigned Registry or Bursary duties.</p>
        @if($student)
            <a href="{{ route('students.admin.index') }}">Back to student directory</a>
        @endif
    </header>
    @if (!$student)
        <section class="sp-panel">
            <h2>Student directory</h2>
            <form class="sp-form" method="GET">
                <label>Search name or student number<input name="search" value="{{ request('search') }}">
                </label>
                <button class="sp-button">Search students</button>
            </form>
            <div class="sp-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Programme</th>
                            <th>Session</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $entry)
                            <tr>
                                <td>
                                    <a href="{{ route('students.admin.show', $entry) }}">{{ $entry->name }}</a>
                                    <br>{{ $entry->student_number }}</td>
                                <td>{{ $entry->programme }}</td>
                                <td>{{ $entry->session }}</td>
                                <td>{{ $entry->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No student records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>{{ $students->links() }}
        </section>
        @can('manage-students')
            <div class="sp-grid">
                <section class="sp-panel">
                    <h2>Create a student account</h2>
                    <form class="sp-form" method="POST" action="{{ route('students.admin.create') }}">@csrf
                        @foreach(['name' => 'Full name', 'student_number' => 'Student number', 'email' => 'Email', 'programme' => 'Programme', 'session' => 'Session', 'cohort' => 'Cohort (optional)', 'institution' => 'Sending institution (optional)'] as $field => $label)
                            <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field) }}" type="{{ $field === 'email' ? 'email' : 'text' }}" @required(!in_array($field, ['cohort', 'institution']))>
                            </label>
                        @endforeach
                        <label>Initial password (at least 12 characters)<input type="password" name="password" autocomplete="new-password" minlength="12" required>
                        </label>
                        <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" required>
                        </label>
                        <button class="sp-button">Create account</button>
                    </form>
                </section>
                <section class="sp-panel">
                    <h2>Add or update a course</h2>
                    <p>Programme and session must match the student record. Use the same course code, programme and session to update a course.</p>
                    <form class="sp-form" method="POST" action="{{ route('students.admin.courses') }}">@csrf
                        @foreach(['code'=>'Course code','title'=>'Course title','programme'=>'Programme','session'=>'Session','credits'=>'Credits','timetable'=>'Timetable / venue'] as $field=>$label)
                            <label>{{ $label }}<input name="{{ $field }}" type="{{ $field === 'credits' ? 'number' : 'text' }}" @required($field !== 'timetable')>
                            </label>
                        @endforeach
                        <label class="sp-check">
                            <input type="checkbox" name="registration_open" value="1">Registration open</label>
                        <button class="sp-button">Save course</button>
                    </form>
                    @foreach($courses as $course)
                        <div class="sp-item">
                            <strong>{{ $course->code }} · {{ $course->title }}</strong>
                            <p>{{ $course->programme }} · {{ $course->session }} · {{ $course->registration_open ? 'Open' : 'Closed' }}</p>
                            <small>{{ $course->timetable }}</small>
                        </div>
                    @endforeach
                </section>
            </div>
            <section class="sp-panel">
                <h2>Publish a notice or calendar date</h2>
                <form class="sp-form" method="POST" action="{{ route('students.admin.notices') }}">@csrf
                    <label>Title<input name="title" required maxlength="255">
                    </label>
                    <label>Message<textarea name="body" required maxlength="10000">
                        </textarea>
                    </label>
                    <label>Programme (blank for all)<input name="programme">
                    </label>
                    <label>Session (blank for all)<input name="session">
                    </label>
                    <label>Calendar date (optional)<input type="date" name="event_date">
                    </label>
                    <button class="sp-button">Publish notice</button>
                </form>
            </section>
        @endcan
    @else
        <section class="sp-panel">
            <h2>Student details</h2>
            <dl class="sp-meta">
                <dt>Number</dt>
                <dd>{{ $student->student_number }}</dd>
                <dt>Email</dt>
                <dd>{{ $student->email }}</dd>
                <dt>Programme</dt>
                <dd>{{ $student->programme }}</dd>
                <dt>Session</dt>
                <dd>{{ $student->session }}</dd>
                <dt>Cohort</dt>
                <dd>{{ $student->cohort }}</dd>
                <dt>Institution</dt>
                <dd>{{ $student->institution }}</dd>
            </dl>
            @can('manage-students')
                <form class="sp-form" method="POST" action="{{ route('students.admin.account', $student) }}">@csrf
                    <label>Account status<select name="status">
                            <option value="active" @selected($student->status === 'active')>Active</option>
                            <option value="inactive" @selected($student->status === 'inactive')>Inactive</option>
                        </select>
                    </label>
                    <label>Placement result<input name="placement_result" value="{{ $student->placement_result }}">
                    </label>
                    <button class="sp-button">Save account</button>
                </form>
            @endcan
        </section>
        @can('manage-students')
            <section class="sp-panel">
                <h2>Registration & results</h2>
                @forelse($enrollments as $entry)
                    <details class="sp-details">
                        <summary>{{ $entry->course->code }} · {{ $entry->course->title }} · {{ $entry->status }}</summary>
                        <form class="sp-form" method="POST" action="{{ route('students.admin.enrollment', [$student, $entry->id]) }}">@csrf
                            <label>Registration<select name="status">
                                    @foreach(['submitted','approved','rejected'] as $status)
                                        <option @selected($entry->status === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Score (0–100)<input type="number" step="0.01" min="0" max="100" name="score" value="{{ $entry->score }}">
                            </label>
                            <label>Grade<input name="grade" value="{{ $entry->grade }}" maxlength="10">
                            </label>
                            <label class="sp-check">
                                <input type="checkbox" name="publish" value="1" @checked($entry->result_published_at)>Publish result to student</label>
                            <button class="sp-button">Save registration & result</button>
                        </form>
                    </details>
                @empty
                    <p>No courses submitted.</p>
                @endforelse
            </section>
            <section class="sp-panel">
                <h2>Student documents</h2>
                @foreach($documents as $document)
                    <div class="sp-item">
                        <a href="{{ route('students.admin.download', [$student, $document->id]) }}">{{ $document->title }}</a>
                        <p>{{ $document->status }}</p>
                        @unless($document->issued_by_staff)
                            <form class="sp-form" method="POST" action="{{ route('students.admin.verify-document', [$student, $document->id]) }}">@csrf
                                <label>Review<select name="status">
                                        <option value="verified">Verified</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </label>
                                <button class="sp-button">Save review</button>
                            </form>
                        @endunless
                    </div>
                @endforeach
                <details class="sp-details">
                    <summary>Issue a letter, certificate or document</summary>
                    <form class="sp-form" method="POST" enctype="multipart/form-data" action="{{ route('students.admin.documents', $student) }}">@csrf
                        <label>Title<input name="title" required>
                        </label>
                        <label>PDF · Maximum 5 MB<input type="file" name="document" accept=".pdf" required>
                        </label>
                        <button class="sp-button">Issue document</button>
                    </form>
                </details>
            </section>
        @endcan
        @can('manage-student-finance')
            <section class="sp-panel">
                <h2>Student billing</h2>
                @foreach($invoices as $invoice)
                    <div class="sp-item">
                        <strong>{{ $invoice->description }} · ₦{{ number_format($invoice->amount_kobo / 100, 2) }}</strong>
                        <p>{{ $invoice->session }} · {{ $invoice->paid_at ? 'Verified paid' : 'Outstanding' }}</p>
                        <small>{{ $invoice->reference }} · RRR {{ $invoice->rrr ?? 'Not generated' }}</small>
                    </div>
                @endforeach
                <details class="sp-details">
                    <summary>Issue a bill</summary>
                    <form class="sp-form" method="POST" action="{{ route('students.admin.invoice', $student) }}">@csrf
                        <input type="hidden" name="reference" value="{{ old('reference', (string) \Illuminate\Support\Str::uuid()) }}">
                        <label>Description<input name="description" required>
                        </label>
                        <label>Amount in kobo (₦1 = 100 kobo)<input type="number" name="amount_kobo" min="1" max="10000000000" required>
                        </label>
                        <label>Session<input name="session" value="{{ $student->session }}" required>
                        </label>
                        <label>Due date<input name="due_date" type="date">
                        </label>
                        <button class="sp-button">Issue bill</button>
                    </form>
                </details>
            </section>
        @endcan
        <section class="sp-panel">
            <h2>Student requests</h2>
            @forelse($requests as $ticket)
                <details class="sp-details">
                    <summary>#{{ $ticket->id }} · {{ $ticket->subject }} · {{ $ticket->category }} · {{ $ticket->status }}</summary>
                    <p>{{ $ticket->body }}</p>
                    @if($ticket->proposed_changes)
                        <dl class="sp-meta">
                            @foreach($ticket->proposed_changes as $field => $value)
                                <dt>{{ str_replace('_', ' ', $field) }}</dt>
                                <dd>{{ $value }}</dd>
                            @endforeach
                        </dl>
                    @endif
                    @if($ticket->decision_note)
                        <p>
                            <strong>Decision</strong> {{ $ticket->decision_note }}</p>
                    @endif
                    @foreach($ticket->messages as $message)
                        <div class="sp-item">
                            <small>{{ $message->from_staff ? 'Staff' : 'Student' }} · {{ $message->created_at->format('j M Y H:i') }}</small>
                            <p>{{ $message->body }}</p>
                        </div>
                    @endforeach
                    <form class="sp-form" method="POST" action="{{ route('students.admin.reply', [$student, $ticket->id]) }}">@csrf
                        <label>Reply<textarea name="body" required maxlength="5000">
                            </textarea>
                        </label>
                        <button class="sp-button">Send reply</button>
                    </form>
                    @can('manage-students')
                        @if($ticket->status === 'open')
                            <form class="sp-form" method="POST" action="{{ route('students.admin.review', [$student, $ticket->id]) }}">@csrf
                                <label>Decision<select name="status">
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                        @unless(in_array($ticket->category, ['profile','accommodation']))
                                            <option value="resolved">Resolve</option>
                                        @endunless
                                    </select>
                                </label>
                                <label>Decision / accommodation allocation details<textarea name="decision_note" required maxlength="5000">
                                    </textarea>
                                </label>
                                <button class="sp-button">Record decision</button>
                            </form>
                        @endif
                    @endcan
                </details>
            @empty
                <p>No requests available for your desk.</p>
            @endforelse
        </section>
    @endif
@endsection
