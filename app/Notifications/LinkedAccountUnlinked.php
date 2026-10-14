<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notice sent to the member when a linked provider account is unlinked
 * from their profile: they must be able to react if the unlink was not
 * theirs.
 */
class LinkedAccountUnlinked extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $provider) {}

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
            ->subject(__('Linked account removed'))
            ->line(__('Your :provider account has just been unlinked from your member account.', ['provider' => $this->provider]))
            ->line(__('If you did not make this change, reset your password right away and warn the team.'));
    }
}
