<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identifier = trim($validated['identifier']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'staff_number';
        $identifier = $field === 'email' ? strtolower($identifier) : strtoupper($identifier);

        if (! Auth::attempt([
            $field => $identifier,
            'password' => $validated['password'],
            'status' => 'active',
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors(['identifier' => 'These sign-in details do not match an active staff account.'])
                ->onlyInput('identifier');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
