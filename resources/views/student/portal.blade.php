@extends('student.layout')
@php
    $heading = match ($section) {
    'dashboard' => 'Welcome, '.$student->name,
    'profile' => 'My profile', 'registration' => 'Course registration', 'timetable' => 'Timetable & calendar',
    'results' => 'Results & placement', 'fees' => 'Fees & receipts', 'documents' => 'My documents',
    'accommodation' => 'Accommodation', 'notices' => 'Notices', 'support' => 'Help & requests', 'settings' => 'Security & preferences',
    };
@endphp
@section('student-content')
    <header class="sp-title">
        <p class="sp-eyebrow">{{ $student->programme }} · {{ $student->session }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $student->institution }}
            @if ($student->cohort)
                · {{ $student->cohort }}
            @endif
        </p>
    </header>
    @if ($section === 'dashboard')
        <div class="sp-grid">
            <div class="sp-stat">Registration<strong>{{ $enrollments->where('course.session', $student->session)->where('status', 'approved')->count() }} approved courses</strong>
                <a href="{{ route('student.section', 'registration') }}">View registration</a>
            </div>
            <div class="sp-stat">Outstanding fees<strong>₦{{ number_format($invoices->whereNull('paid_at')->sum('amount_kobo') / 100, 2) }}</strong>
                <a href="{{ route('student.section', 'fees') }}">View fee ledger</a>
            </div>
            <section class="sp-panel">
                <h2>Latest notices</h2>
                @forelse ($notices->take(3) as $notice)
                    <article class="sp-item">
                        <h3>{{ $notice->title }}</h3>
                        <p>{{ $notice->body }}</p>
                        <small>{{ $notice->published_at->format('j M Y') }}</small>
                    </article>
                @empty
                    <p class="sp-empty">There are no notices for your programme yet.</p>
                @endforelse
            </section>
            <section class="sp-panel">
                <h2>Upcoming activities</h2>
                @forelse ($notices->whereNotNull('event_date')->filter(fn ($notice) => $notice->event_date->gte(today()))->sortBy('event_date') as $notice)
                    <div class="sp-item">
                        <strong>{{ $notice->event_date->format('j M Y') }}</strong>
                        <p>{{ $notice->title }}</p>
                    </div>
                @empty
                    <p class="sp-empty">No activities have been scheduled yet.</p>
                @endforelse
                <a href="{{ route('student.section', 'timetable') }}">Open timetable & calendar</a>
            </section>
        </div>
    @elseif ($section === 'profile')
        <div class="sp-grid">
            <section class="sp-panel">
                <h2>Student record</h2>
                <dl class="sp-meta">
                    <dt>Student number</dt>
                    <dd>{{ $student->student_number }}</dd>
                    <dt>Email</dt>
                    <dd>{{ $student->email }}</dd>
                    <dt>Programme</dt>
                    <dd>{{ $student->programme }}</dd>
                    <dt>Session</dt>
                    <dd>{{ $student->session }}</dd>
                    <dt>Institution</dt>
                    <dd>{{ $student->institution ?? 'Not recorded' }}</dd>
                    <dt>Status</dt>
                    <dd>{{ ucfirst($student->status) }}</dd>
                </dl>
                <p>Registry reviews biodata corrections before they update your record.</p>
            </section>
            <section class="sp-panel">
                <h2>Request a correction</h2>
                <form class="sp-form" action="{{ route('student.profile') }}" method="POST">@csrf
                    <label>Full name<input name="name" value="{{ old('name', $student->name) }}" required maxlength="255">
                    </label>
                    <label>Phone<input name="phone" value="{{ old('phone', $student->phone) }}" maxlength="40">
                    </label>
                    <label>Address<textarea name="address" maxlength="2000">{{ old('address', $student->address) }}</textarea>
                    </label>
                    <label>Date of birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                    </label>
                    <button class="sp-button">Send to Registry</button>
                </form>
            </section>
        </div>
    @elseif ($section === 'registration')
        <section class="sp-panel">
            <h2>Available courses</h2>
            <form class="sp-form" method="POST" action="{{ route('student.register') }}">@csrf
                @forelse ($courses as $course)
                    @php $enrollment = $enrollments->firstWhere('student_course_id', $course->id);
                    @endphp
                    <label class="sp-check">
                        <input type="checkbox" name="courses[]" value="{{ $course->id }}" @disabled(!$course->registration_open || $enrollment)>
                        <span>
                            <strong>{{ $course->code }} · {{ $course->title }}</strong>
                            <br>{{ $course->credits }} credits · {{ $enrollment ? ucfirst($enrollment->status) : ($course->registration_open ? 'Registration open' : 'Registration closed') }}</span>
                    </label>
                @empty
                    <p class="sp-empty">No courses have been assigned to your programme and session.</p>
                @endforelse
                @if ($courses->where('registration_open', true)->isNotEmpty())
                    <button class="sp-button">Submit selected courses</button>
                @endif
            </form>
        </section>
    @elseif ($section === 'timetable')
        <section class="sp-panel">
            <h2>Class timetable</h2>
            @forelse ($courses as $course)
                <div class="sp-item">
                    <h3>{{ $course->code }} · {{ $course->title }}</h3>
                    <p>{{ $course->timetable ?? 'Schedule awaiting publication' }}</p>
                </div>
            @empty
                <p class="sp-empty">Your timetable will appear here when Academic publishes your courses.</p>
            @endforelse
        </section>
        <section class="sp-panel">
            <h2>Academic calendar</h2>
            @forelse ($notices->whereNotNull('event_date')->sortBy('event_date') as $notice)
                <div class="sp-item">
                    <strong>{{ $notice->event_date->format('j F Y') }}</strong>
                    <p>{{ $notice->title }}</p>
                    <p>{{ $notice->body }}</p>
                </div>
            @empty
                <p class="sp-empty">Calendar dates have not been published yet.</p>
            @endforelse
        </section>
    @elseif ($section === 'results')
        <section class="sp-panel">
            <h2>Placement</h2>
            <p>{{ $student->placement_result ?? 'A placement result has not been published for your account.' }}</p>
        </section>
        <section class="sp-panel">
            <h2>Published academic results</h2>
            @php $publishedResults = $enrollments->filter(fn ($enrollment) => $enrollment->result_published_at && $enrollment->result_published_at->lte(now()));
            @endphp
            @if ($publishedResults->isNotEmpty())
                <div class="sp-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Session</th>
                                <th>Score</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($publishedResults as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->course->code }} · {{ $enrollment->course->title }}</td>
                                    <td>{{ $enrollment->course->session }}</td>
                                    <td>{{ $enrollment->score }}</td>
                                    <td>{{ $enrollment->grade }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sp-actions">
                    <a class="sp-button" href="{{ route('student.results.statement') }}">Download results statement</a>
                </div>
            @else
                <p class="sp-empty">Your results will appear after Academic releases them.</p>
            @endif
        </section>
    @elseif ($section === 'fees')
        <section class="sp-panel">
            <h2>Student fee ledger</h2>
            <dl class="sp-meta">
                <dt>Total billed</dt>
                <dd>₦{{ number_format($invoices->sum('amount_kobo') / 100, 2) }}</dd>
                <dt>Verified payments</dt>
                <dd>₦{{ number_format($invoices->whereNotNull('paid_at')->sum('amount_kobo') / 100, 2) }}</dd>
                <dt>Outstanding</dt>
                <dd>₦{{ number_format($invoices->whereNull('paid_at')->sum('amount_kobo') / 100, 2) }}</dd>
            </dl>
            @forelse ($invoices as $invoice)
                <article class="sp-item">
                    <h3>{{ $invoice->description }}</h3>
                    <p>{{ $invoice->session }} · ₦{{ number_format($invoice->amount_kobo / 100, 2) }} · {{ $invoice->paid_at ? 'Paid '.$invoice->paid_at->format('j M Y') : 'Outstanding' }}</p>
                    <small>Bill reference {{ $invoice->reference }}
                        @if($invoice->due_date)
                            · Due {{ $invoice->due_date->format('j M Y') }}
                        @endif
                    </small>
                    @if ($invoice->rrr)
                        <p>
                            <strong>Remita reference (RRR) {{ $invoice->rrr }}</strong>
                        </p>
                        <p>Use this RRR to pay through Remita or a bank, then check the payment here.</p>
                    @endif
                    <div class="sp-actions">
                        @if ($invoice->paid_at)
                            <a class="sp-button" href="{{ route('student.fees.receipt', $invoice->id) }}">Download receipt</a>
                        @elseif ($invoice->rrr)<form method="POST" action="{{ route('student.fees.verify', $invoice->id) }}">@csrf
                                <button class="sp-button">Check payment status</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('student.fees.pay', $invoice->id) }}">@csrf
                                <button class="sp-button">Generate Remita reference</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <p class="sp-empty">Bursary has not issued any bills to your account.</p>
            @endforelse
        </section>
    @elseif ($section === 'documents')
        <div class="sp-grid">
            <section class="sp-panel">
                <h2>Your documents</h2>
                @forelse ($documents as $document)
                    <div class="sp-item">
                        <h3>{{ $document->title }}</h3>
                        <p>{{ ucfirst($document->status) }} · {{ $document->issued_by_staff ? 'Issued by NFLV' : 'Student upload' }}</p>
                        <a href="{{ route('student.documents.download', $document->id) }}">Download document</a>
                    </div>
                @empty
                    <p class="sp-empty">Your uploads and official letters will appear here.</p>
                @endforelse
            </section>
            <section class="sp-panel">
                <h2>Upload a document</h2>
                <form class="sp-form" method="POST" enctype="multipart/form-data" action="{{ route('student.documents.store') }}">@csrf
                    <label>Document title<input name="title" required maxlength="255">
                    </label>
                    <label>PDF, JPG or PNG · Maximum 5 MB<input name="document" type="file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </label>
                    <button class="sp-button">Upload for review</button>
                </form>
            </section>
        </div>
    @elseif (in_array($section, ['accommodation', 'support']))
        <div class="sp-grid">
            <section class="sp-panel">
                <h2>{{ $section === 'accommodation' ? 'Request accommodation' : 'Send a request' }}</h2>
                <form class="sp-form" method="POST" action="{{ route('student.requests.store') }}">@csrf
                    @if ($section === 'accommodation')
                        <input type="hidden" name="category" value="accommodation">
                        <p>Registry will review availability and publish any allocation in your request history. Include your preferred dates and any access requirements.</p>
                    @else
                        <label>Category<select name="category">
                                @foreach (['academic' => 'Academic / registration', 'finance' => 'Fees / payments', 'technical' => 'Portal access', 'general' => 'General enquiry'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label>Subject<input name="subject" required maxlength="255">
                    </label>
                    <label>Details<textarea name="body" required maxlength="5000">
                        </textarea>
                    </label>
                    <button class="sp-button">Submit request</button>
                </form>
            </section>
            <section class="sp-panel">
                <h2>Request history</h2>
                @forelse ($requests->filter(fn ($ticket) => $section === 'support' || $ticket->category === 'accommodation') as $ticket)
                    <details class="sp-details">
                        <summary>#{{ $ticket->id }} · {{ $ticket->subject }} · {{ ucfirst($ticket->status) }}</summary>
                        <p>{{ $ticket->body }}</p>
                        @if($ticket->decision_note)
                            <p>
                                <strong>NFLV decision</strong>
                                <br>{{ $ticket->decision_note }}</p>
                        @endif
                        @foreach ($ticket->messages as $message)
                            <div class="sp-item">
                                <small>{{ $message->from_staff ? 'NFLV staff' : 'You' }} · {{ $message->created_at->format('j M Y H:i') }}</small>
                                <p>{{ $message->body }}</p>
                            </div>
                        @endforeach
                        @if ($ticket->status === 'open')
                            <form class="sp-form" method="POST" action="{{ route('student.requests.reply', $ticket->id) }}">@csrf
                                <label>Reply<textarea name="body" required maxlength="5000">
                                    </textarea>
                                </label>
                                <button class="sp-button">Send reply</button>
                            </form>
                        @endif
                    </details>
                @empty
                    <p class="sp-empty">You have no requests yet.</p>
                @endforelse
            </section>
        </div>
    @elseif ($section === 'notices')
        <section class="sp-panel">
            @forelse ($notices as $notice)
                <article class="sp-item">
                    <h2>{{ $notice->title }}</h2>
                    <p>{{ $notice->body }}</p>
                    <small>Published {{ $notice->published_at->format('j F Y') }}</small>
                </article>
            @empty
                <p class="sp-empty">No notices have been published for your account.</p>
            @endforelse
        </section>
    @elseif ($section === 'settings')
        <section class="sp-panel">
            <h2>Password & notifications</h2>
            <form class="sp-form" action="{{ route('student.settings') }}" method="POST">@csrf
                <label>Current password<input name="current_password" type="password" autocomplete="current-password" required>
                </label>
                <label>New password (optional, at least 12 characters)<input name="password" type="password" minlength="12" autocomplete="new-password">
                </label>
                <label>Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password">
                </label>
                <label class="sp-check">
                    <input type="checkbox" name="email_notices" value="1" @checked($student->email_notices)>Include me in optional student notice emails when they are sent.</label>
                <p>All notices are available in your portal regardless of this preference.</p>
                <button class="sp-button">Save changes</button>
            </form>
        </section>
    @endif
@endsection
