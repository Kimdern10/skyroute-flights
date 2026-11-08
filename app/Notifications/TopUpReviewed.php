<?php

namespace App\Notifications;

use App\Enums\TopUpStatus;
use App\Models\Setting;
use App\Models\TopUpRequest;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the customer their top-up was added to the wallet, or why it was not.
 * Sent straight away (no queue).
 */
class TopUpReviewed extends Notification
{
    public function __construct(public TopUpRequest $topUp) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = Money::format($this->topUp->amount_cents);
        $message = (new MailMessage)->greeting('Hello '.str($this->topUp->user->name)->before(' ').',');

        if ($this->topUp->status === TopUpStatus::Approved) {
            $message->subject("{$amount} added to your wallet")
                ->line("We've added {$amount} to your ".Setting::get('business_name').' wallet.')
                ->line('Your balance is now '.$this->topUp->user->balance().'.');
        } else {
            $message->subject('Your top-up of '.$amount.' was not added')
                ->line("We could not add your top-up of {$amount} (reference {$this->topUp->payment_reference}).");

            if (filled($this->topUp->admin_note)) {
                $message->line('Reason: '.$this->topUp->admin_note);
            }

            $message->line('Questions? Email '.Setting::get('support_email').'.');
        }

        if (filled($this->topUp->admin_note) && $this->topUp->status === TopUpStatus::Approved) {
            $message->line('Note: '.$this->topUp->admin_note);
        }

        return $message->action('Open my wallet', route('wallet'))
            ->salutation("Safe travels,\nThe ".Setting::get('business_name').' team');
    }
}
