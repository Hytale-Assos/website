<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Confirmation link sent to the NEW school email address when a member
 * requests a school email change: the address only becomes their
 * confirmed school email once the link in this mail is clicked, proving
 * they control the mailbox.
 */
class SchoolEmailConfirmation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $verificationUrl,
        public readonly Carbon $expiresAt,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Confirm your school email address'))
            ->line(__('You requested to use this address as your school email on the member portal.'))
            ->line(__('Confirm it below; the link expires in 24 hours. Until then your current school email stays unchanged.'))
            ->action(__('Confirm school email'), $this->verificationUrl)
            ->line(__('If you did not request this change, you can safely ignore this email.'));
    }
}
