<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Rules\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'cf-turnstile-response' => filled(config('services.turnstile.secret_key'))
                ? ['required', new Turnstile]
                : ['sometimes'],
        ]);

        Mail::to(config('mail.from.address'))->send(new ContactMessageReceived(
            name: $data['name'],
            email: $data['email'],
            body: $data['message'],
        ));

        return redirect()->route('contact.index')->with('status', __('Bedankt! Je bericht is verstuurd.'));
    }
}
