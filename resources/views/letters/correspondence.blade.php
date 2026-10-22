<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $document->reference_number }}</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #1c2b28; line-height: 1.6; }
            .letterhead { border-bottom: 2px solid #173d35; padding-bottom: 12px; margin-bottom: 24px; text-align: center; }
            .letterhead h1 { font-size: 15pt; margin: 0; letter-spacing: 1px; }
            .letterhead p { margin: 4px 0 0; font-size: 10pt; color: #4a5a55; }
            .reference { margin-bottom: 24px; font-size: 10pt; }
            .reference span { display: block; }
            .subject { font-weight: bold; margin: 24px 0 18px; }
            .body p { margin: 0 0 12px; }
            .signature { margin-top: 48px; }
            .signature .name { font-weight: bold; }
            .signature .role { font-size: 10pt; color: #4a5a55; }
            .placeholder { background: #f4f1e8; border: 1px dashed #b9a77c; padding: 10px; font-size: 9pt; color: #6b5c33; }
        </style>
    </head>
    <body>
        <div class="letterhead">
            <h1>NIGERIA FRENCH LANGUAGE VILLAGE</h1>
            <p>Badagry, Lagos State, Nigeria · Registry Department</p>
            <p class="placeholder">Official letterhead artwork and approved wording are awaiting institutional confirmation.</p>
        </div>

        <div class="reference">
            <span>Reference: {{ $document->reference_number }}</span>
            <span>Date: {{ ($document->issued_at ?? now())->format('d F Y') }}</span>
        </div>

        <p>{{ $recipientName }}</p>
        <p>Dear {{ $recipientName }},</p>

        <p class="subject">Re: {{ $document->subject }}</p>

        <div class="body">
            {!! nl2br(e(strip_tags($body))) !!}
        </div>

        <div class="signature">
            <p class="name">{{ $document->issuer?->name ?? 'Authorised Registry Officer' }}</p>
            <p class="role">{{ $document->issuer?->role?->label() ?? 'Registry Department' }}</p>
            <p>For: Registrar</p>
        </div>
    </body>
</html>
