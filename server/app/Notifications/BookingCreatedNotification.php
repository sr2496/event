<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Event $event
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Booking Request - ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have received a new booking request.')
            ->line('**Event Details:**')
            ->line('- Title: ' . $this->event->title)
            ->line('- Type: ' . $this->event->type)
            ->line('- Date: ' . $this->event->event_date->format('F j, Y'))
            ->line('- City: ' . $this->event->city)
            ->line('- Venue: ' . ($this->event->venue ?? 'To be confirmed'))
            ->action('View Booking Details', url('/vendor/bookings/' . $this->event->id))
            ->line('Please review and respond to this booking request.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'booking_created',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'message' => 'New booking request for ' . $this->event->title,
        ];
    }
}
