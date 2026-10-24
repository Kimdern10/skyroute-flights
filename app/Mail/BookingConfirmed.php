<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Setting;
use App\Support\ETicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent straight away (no queue) when a ticket is issued, with the PDF e-ticket attached.
 */
class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your e-ticket {$this->booking->reference}: {$this->booking->origin} to {$this->booking->destination}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-confirmed',
            with: ['site' => Setting::values()],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => ETicket::pdf($this->booking), ETicket::filename($this->booking))
                ->withMime('application/pdf'),
        ];
    }
}
