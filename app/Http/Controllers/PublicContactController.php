<?php

namespace App\Http\Controllers;

use App\Mail\PublicContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PublicContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $enquiry = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            Mail::to('info@frenchvillage.edu.ng')->send(new PublicContactMessage($enquiry));
        } catch (TransportExceptionInterface $error) {
            report($error);

            return back()->withInput()->withErrors(['delivery' => 'Your message could not be sent. Please try again or email info@frenchvillage.edu.ng directly.']);
        }

        return redirect()->to(route('website.contact').'#contact-message')
            ->with('contact_status', 'Thank you. Your message has been submitted to NFLV.');
    }
}
