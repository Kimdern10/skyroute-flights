<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the 6-digit code a new customer types in to confirm their email address.
 * Sent straight away (no queue), so it arrives while they are on the page.
 */
class VerifyEmailCode extends Notification
{
    public function __construct(public string $code, public int $minutes) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $business = Setting::get('business_name');

        return (new MailMessage)
            ->subject("{$this->code} is your {$business} code")
            ->markdown('mail.verify-email-code', [
                'code' => $this->code,
                'minutes' => $this->minutes,
                'business' => $business,
                'name' => (string) ($notifiable->name ?? ''),
            ]);
    }
}
