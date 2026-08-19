<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Nieuw contactformulier van :name', ['name' => $this->name]),
            replyTo: [$this->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
        );
    }
}
