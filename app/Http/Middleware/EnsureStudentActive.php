<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('student')->check()) {
            return redirect()->route('student.login');
        }
        if (Auth::guard('student')->user()->refresh()->status !== 'active') {
            Auth::guard('student')->logout();

            return redirect()->route('student.login')->withErrors(['identifier' => 'Your account is inactive. Contact Registry for assistance.']);
        }

        return $next($request);
    }
}
