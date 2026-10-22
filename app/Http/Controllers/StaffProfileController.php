<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->staffProfile()->firstOrCreate([], ['employment_status' => 'unknown']);

        return view('profile.show', [
            'user' => $user,
            'profile' => $profile->load('department'),
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
        ]);

        $profile = $request->user()->staffProfile()->firstOrCreate([], ['employment_status' => 'unknown']);
        $profile->fill($validated)->save();

        return redirect()->route('profile.show')->with('status', 'Contact details updated.');
    }
}
