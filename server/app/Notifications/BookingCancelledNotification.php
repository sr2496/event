<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
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
            ->subject('Booking Cancelled - ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name)
            ->line('A booking has been cancelled by the client.')
            ->line('**Event Details:**')
            ->line('- Title: ' . $this->event->title)
            ->line('- Date: ' . $this->event->event_date->format('F j, Y'))
            ->line('- City: ' . $this->event->city)
            ->line('**Cancellation Reason:** ' . $this->reason)
            ->line('Your calendar has been automatically updated to reflect this change.')
            ->action('View Dashboard', url('/vendor/dashboard'))
            ->line('Thank you for your understanding.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'reason' => $this->reason,
            'message' => 'Booking for ' . $this->event->title . ' has been cancelled',
        ];
    }
}
