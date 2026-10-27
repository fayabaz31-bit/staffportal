<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->staffProfile()->firstOrCreate([], ['employment_status' => 'unknown']);

        return view('profile.show', [
            'user' => $user,
            'profile' => $profile->load(['department', 'documents']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'changeRequests' => $user->profileChangeRequests()->latest()->get(),
        ]);
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:40'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'residential_address' => ['nullable', 'string', 'max:1000'],
            'state_of_origin' => ['nullable', 'string', 'max:64'],
            'educational_qualifications' => ['nullable', 'string', 'max:2000'],
            'professional_qualifications' => ['nullable', 'string', 'max:2000'],
            'pencom_number' => ['nullable', 'string', 'max:64'],
            'pfa_name' => ['nullable', 'string', 'max:128'],
            'passport_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $profile = $request->user()->staffProfile()->firstOrCreate([], ['employment_status' => 'unknown']);
        $profile->fill(collect($validated)->except('passport_photo')->all())->save();

        if ($request->hasFile('passport_photo')) {
            if ($profile->passport_path) {
                Storage::disk('public')->delete($profile->passport_path);
            }
            $path = $request->file('passport_photo')->store('staff/passports', 'public');
            $profile->update(['passport_path' => $path]);
            AuditLog::record('profile.passport_updated', $profile);
        }

        AuditLog::record('profile.personal_details_updated', $profile);

        return redirect()->route('profile.show')->with('status', 'Profile details updated.');
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $profile = $request->user()->staffProfile()->firstOrCreate([], ['employment_status' => 'unknown']);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['certificate', 'id_card', 'appointment_letter', 'promotion_letter', 'other'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('document');
        $path = $file->store('staff/documents', 'public');

        $profile->documents()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::record('profile.document_uploaded', $profile, [
            'title' => $validated['title'],
            'type' => $validated['type'],
        ]);

        return redirect()->route('profile.show')->with('status', 'Document uploaded successfully.');
    }
}
