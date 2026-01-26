<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventCompletedNotification extends Notification implements ShouldQueue
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
            ->subject('Event Completed - ' . $this->event->title)
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('The event has been marked as completed.')
            ->line('**Event Details:**')
            ->line('- Title: ' . $this->event->title)
            ->line('- Date: ' . $this->event->event_date->format('F j, Y'))
            ->line('- City: ' . $this->event->city)
            ->line('Thank you for successfully completing this event!')
            ->line('Your reliability score has been updated positively.')
            ->line('The final payment will be processed according to the payment schedule.')
            ->action('View Dashboard', url('/vendor/dashboard'))
            ->line('We look forward to working with you again!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'event_completed',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'message' => 'Event ' . $this->event->title . ' has been completed',
        ];
    }
}
