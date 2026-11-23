@extends('layouts.public-information')

@section('title', 'Leadership | Nigeria French Language Village')
@section('information-heading', 'Our leadership')

@section('information-content')
    <h2>Principal Officers</h2>
    <article class="nfv-officer-profile">
        <img src="{{ asset('media/officer-kolawole.jpg') }}" alt="" width="112" height="140" loading="lazy">
        <div>
            <h3>Prof. Samuel Oladiipo Kolawole (FNITI)</h3>
            <p><strong>Director &amp; Chief Executive Officer</strong><br>
                PhD in French (Translation Studies), University of Ibadan (2005). MA in Translation Studies, University of Benin (1994). BA in French, Ondo State University (1988).<br>
                <a href="{{ route('website.director-welcome') }}">Read the Director’s welcome</a>.
            </p>
        </div>
    </article>

    <article class="nfv-officer-profile">
        <img src="{{ asset('media/officer-ajayi.jpg') }}" alt="" width="112" height="140" loading="lazy">
        <div>
            <h3>Prof. S. A. Ajayi</h3>
            <p><strong>Deputy Director</strong><br>
                PhD in Literature and Stylistics, University of Ibadan (2009). Master’s degree in French Studies, University of Lagos (1989). BA (Hons) in French, Ogun State University, now Olabisi Onabanjo University (1986).
            </p>
        </div>
    </article>

    <article class="nfv-officer-profile">
        <img src="{{ asset('media/officer-nwa.jpg') }}" alt="" width="112" height="140" loading="lazy">
        <div>
            <h3>Miss Ini Nwa</h3>
            <p><strong>Registrar &amp; Secretary to Council</strong><br>
                Master of Public Administration, Lagos State University (2001). BA (Hons) in French, University of Ilorin (1989).<br>
                <a href="tel:+2347088637496">07088637496</a>
            </p>
        </div>
    </article>

    <article class="nfv-officer-profile">
        <img src="{{ asset('media/officer-ibikunle.jpg') }}" alt="" width="112" height="140" loading="lazy">
        <div>
            <h3>Mr. Lateef Oyeleke Ibikunle</h3>
            <p><strong>Bursar</strong><br>
                MSc in Accounting, MBA in Accounting and Finance, HND in Accounting, and OND in Accountancy.
            </p>
        </div>
    </article>

    <article class="nfv-officer-profile">
        <img src="{{ asset('media/officer-adejo.jpg') }}" alt="" width="112" height="140" loading="lazy">
        <div>
            <h3>Dr. Augustine Adejo</h3>
            <p><strong>Acting Librarian</strong><br>
                PhD, MLS, PGDE, and BA (Hons) in French.<br>
                <a href="tel:+2348083393875">08033938750</a>
            </p>
        </div>
    </article>
    <p><a href="https://frenchvillage.edu.ng/management">Official NFLV Principal Officers listing</a></p>

    <h2>Governing Council</h2>
    <p>Alhaji Labiru Musa Kafur, Chairman.</p>

    <h2>Deans</h2>
    <p><a href="{{ route('website.contact') }}">Contact NFLV</a> for information about academic leadership and additional council members.</p>
@endsection
