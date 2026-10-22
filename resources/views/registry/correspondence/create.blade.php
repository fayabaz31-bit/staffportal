@extends('layouts.app')

@section('title', 'Prepare correspondence | NfLV Staff Portal')

@section('content')
    <div class="dashboard-page">
        <header class="dashboard-header">
            <a class="wordmark dashboard-wordmark" href="{{ route('dashboard') }}">
                <span class="wordmark-name">NfLV</span>
                <span class="wordmark-divider" aria-hidden="true"></span>
                <span class="wordmark-caption">Staff Portal</span>
            </a>
            <div class="account-tools">
                <span class="account-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">Sign out</button>
                </form>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="dashboard-rail" aria-label="Correspondence navigation">
                <p class="rail-label">REGISTRY</p>
                <a class="rail-link" href="{{ route('dashboard') }}">Overview</a>
                <a class="rail-link" href="{{ route('registry.correspondence.index') }}">Correspondence</a>
                <a class="rail-link rail-link-active" href="{{ route('registry.correspondence.create') }}"><span class="rail-dot"></span> Prepare</a>
                <div class="rail-bottom"><span class="status-dot"></span> DRAFT ONLY</div>
            </aside>

            <main class="dashboard-main">
                <div class="dashboard-kicker">
                    <span>NEW DOCUMENT</span>
                    <a class="back-link" href="{{ route('registry.correspondence.index') }}">Back to correspondence</a>
                </div>
                <section class="dashboard-welcome">
                    <p class="eyebrow eyebrow-dark">REGISTRY CORRESPONDENCE</p>
                    <h1>Prepare correspondence</h1>
                    <p>Placeholders are filled from the selected recipient when a template is available. The approved template wording must still be confirmed by management.</p>
                </section>

                <p class="profile-notice">
                    Approval routing comes from the category's published workflow version. Institution-reserved
                    matters and final signing authority remain <strong>awaiting administrative configuration</strong>;
                    the seeded pilot routes are not a declaration of policy.
                </p>

                @if ($errors->any())
                    <div class="profile-notice profile-notice-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="panel-form" method="POST" action="{{ route('registry.correspondence.store') }}">
                    @csrf

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="correspondence_category_id">Category</label>
                            <select id="correspondence_category_id" name="correspondence_category_id" required>
                                <option value="">Select a category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('correspondence_category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="correspondence_template_id">Template (optional)</label>
                            <select id="correspondence_template_id" name="correspondence_template_id">
                                <option value="">No template</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected(old('correspondence_template_id', $selectedTemplate?->id) == $template->id)>{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="recipient_user_id">Recipient (staff account)</label>
                            <select id="recipient_user_id" name="recipient_user_id">
                                <option value="">No portal account</option>
                                @foreach ($recipients as $recipient)
                                    <option value="{{ $recipient->id }}" @selected(old('recipient_user_id') == $recipient->id)>{{ $recipient->name }} — {{ $recipient->staff_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="recipient_name">Recipient name (if no account)</label>
                            <input id="recipient_name" name="recipient_name" type="text" maxlength="255" value="{{ old('recipient_name') }}">
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="subject">Subject</label>
                        <input id="subject" name="subject" type="text" maxlength="255" value="{{ old('subject', $selectedTemplate?->subject_template) }}" required>
                    </div>

                    <div class="form-field">
                        <label for="body">Body</label>
                        <textarea id="body" name="body" rows="12" maxlength="50000" required>{{ old('body', $selectedTemplate?->body_template) }}</textarea>
                        <p class="form-hint">
                            Available placeholders:
                            <code>@{{ staff_name }}</code>, <code>@{{ staff_number }}</code>, <code>@{{ year }}</code>, <code>@{{ body }}</code>.
                        </p>
                    </div>

                    <button class="button button-primary" type="submit">Save draft</button>
                </form>
            </main>
        </div>
    </div>
@endsection
