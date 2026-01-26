<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
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
        $primaryVendor = $this->event->primaryVendor;
        $vendorName = $primaryVendor?->vendor?->business_name ?? 'the vendor';

        return (new MailMessage)
            ->subject('Booking Confirmed - ' . $this->event->title)
            ->greeting('Great news, ' . $notifiable->name . '!')
            ->line('Your booking has been confirmed by ' . $vendorName . '.')
            ->line('**Event Details:**')
            ->line('- Title: ' . $this->event->title)
            ->line('- Date: ' . $this->event->event_date->format('F j, Y'))
            ->line('- City: ' . $this->event->city)
            ->line('**Next Steps:**')
            ->line('Please complete the advance payment to finalize your booking.')
            ->action('Pay Advance', url('/client/events/' . $this->event->id . '/payment'))
            ->line('Thank you for choosing our platform!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'booking_confirmed',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'message' => 'Your booking for ' . $this->event->title . ' has been confirmed',
        ];
    }
}
