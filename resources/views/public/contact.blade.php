@extends('layouts.public-information')

@section('title', 'Contact Us | Nigeria French Language Village')
@section('information-page-class', 'nfv-contact-page')
@section('information-heading', 'Contact us')
@section('information-intro', 'Get in touch with the Nigeria French Language Village, Badagry.')

@section('information-content')
    <div class="nfv-contact-grid">
        <aside class="nfv-contact-info" aria-labelledby="contact-info-heading">
            <h2 id="contact-info-heading">Contact information</h2>
            <p>For general enquiries, registration or academic matters, contact the relevant office below.</p>
            <dl class="nfv-contact-details">
                <div>
                    <dt>General enquiries</dt>
                    <dd><a href="mailto:info@frenchvillage.edu.ng">info@frenchvillage.edu.ng</a></dd>
                </div>
                <div>
                    <dt>Registry</dt>
                    <dd><a href="mailto:registrar@frenchvillage.edu.ng">registrar@frenchvillage.edu.ng</a></dd>
                </div>
                <div>
                    <dt>Academic Affairs</dt>
                    <dd><a href="mailto:academicaffairs@frenchvillage.edu.ng">academicaffairs@frenchvillage.edu.ng</a></dd>
                </div>
            </dl>
            <div class="nfv-contact-address">
                <h3>Visit the Village</h3>
                <address>The Nigeria French Language Village<br>Badagry, Lagos State, Nigeria</address>
                <a href="#campus-location">Find us on the map</a>
            </div>
        </aside>

        <section class="nfv-contact-message" id="contact-message" aria-labelledby="contact-message-heading">
            <h2 id="contact-message-heading">Send your message</h2>
            @if (session('contact_status'))
                <p class="nfv-contact-feedback" role="status">{{ session('contact_status') }}</p>
            @endif
            @if ($errors->any())
                <div class="nfv-contact-errors" role="alert">
                    <p>Please check your message.</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('website.contact.send') }}" method="POST" class="nfv-contact-form">
                @csrf
                <div class="nfv-contact-fields">
                    <label for="contact-name">Full name
                        <input id="contact-name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="100" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" required>
                    </label>
                    <label for="contact-email">Email address
                        <input id="contact-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required>
                    </label>
                </div>
                <label for="contact-subject">Subject
                    <input id="contact-subject" name="subject" value="{{ old('subject') }}" maxlength="160" aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}" required>
                </label>
                <label for="contact-body">Message
                    <textarea id="contact-body" name="message" rows="6" maxlength="5000" aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}" required>{{ old('message') }}</textarea>
                </label>
                <button type="submit">Send message</button>
            </form>
        </section>
    </div>

    <section class="nfv-contact-support" aria-labelledby="contact-support-heading">
        <div>
            <h2 id="contact-support-heading">Portal &amp; service support</h2>
            <p>Having trouble with registration, payments or portal access? Open the relevant portal to get help, or email General Enquiries if you cannot sign in.</p>
        </div>
        <div class="nfv-contact-support-links">
            <a href="{{ route('student.login') }}">Student portal</a>
            <a href="{{ route('home') }}">Staff portal</a>
        </div>
    </section>
@endsection

@section('information-media')
    <div id="campus-location">
        @include('partials.public-campus-map')
    </div>
@endsection
