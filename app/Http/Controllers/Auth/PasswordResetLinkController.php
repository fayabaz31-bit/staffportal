<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the request-a-reset-link form.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link without revealing whether the email exists.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Please wait before requesting another reset link.']);
        }

        // Generic response for both sent and unknown-user to prevent enumeration.
        return back()->with('status', 'If an account matches that email, a password reset link has been sent.');
    }
}
