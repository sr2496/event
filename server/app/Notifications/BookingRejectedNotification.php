<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Event $event,
        protected string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Update - ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name)
            ->line('Unfortunately, the vendor was unable to accept your booking request.')
            ->line('**Event:** ' . $this->event->title)
            ->line('**Date:** ' . $this->event->event_date->format('F j, Y'))
            ->line('**Reason:** ' . $this->reason)
            ->line('Your assurance fee will be refunded to your original payment method.')
            ->action('Browse Other Vendors', url('/vendors'))
            ->line('We encourage you to explore other vendors on our platform.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'booking_rejected',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'reason' => $this->reason,
            'message' => 'Your booking for ' . $this->event->title . ' was not accepted',
        ];
    }
}
