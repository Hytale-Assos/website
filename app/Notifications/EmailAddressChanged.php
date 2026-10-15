<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security notice sent to the PREVIOUS email address when a member
 * changes the address they sign in with: that address no longer
 * controls the account, and its owner must be able to react if the
 * change was not theirs.
 */
class EmailAddressChanged extends Notification
{
    use Queueable;

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
            ->subject(__('Email address changed'))
            ->line(__('The email address used to sign in to your account has just been changed.'))
            ->line(__('The new address must verify the link we sent it before the account is fully active again.'))
            ->line(__('If you did not make this change, reset your password right away and warn the team.'))
            ->action(__('Reset password'), route('password.request'));
    }
}
