<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OfficialLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficialLetterController extends Controller
{
    /**
     * Download or view an official letter with strict IDOR protections and anti-cache headers.
     */
    public function download(Request $request, OfficialLetter $letter): StreamedResponse
    {
        Gate::authorize('view-official-letter', $letter);

        if (! Storage::disk('private')->exists($letter->storage_path)) {
            abort(404, 'The requested official document file cannot be found in private storage.');
        }

        $fileName = 'Official_Letter_'.str_replace('/', '_', $letter->reference_number).'.pdf';

        AuditLog::record('official_letter.downloaded', $letter, [
            'reference_number' => $letter->reference_number,
            'document_type' => $letter->document_type,
        ]);

        return Storage::disk('private')->response(
            $letter->storage_path,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }
}
