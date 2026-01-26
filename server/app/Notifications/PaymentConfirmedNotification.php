<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Event $event,
        protected string $paymentType
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $paymentLabel = match ($this->paymentType) {
            'assurance_fee' => 'assurance fee',
            'advance' => 'advance payment',
            default => 'payment',
        };

        return (new MailMessage)
            ->subject('Payment Confirmed - ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A ' . $paymentLabel . ' has been confirmed for your booking.')
            ->line('**Event Details:**')
            ->line('- Title: ' . $this->event->title)
            ->line('- Date: ' . $this->event->event_date->format('F j, Y'))
            ->line('- City: ' . $this->event->city)
            ->when($this->paymentType === 'advance', function ($message) {
                return $message->line('The event is now fully confirmed. Please prepare for the event accordingly.');
            })
            ->action('View Booking Details', url('/vendor/bookings/' . $this->event->id))
            ->line('Thank you for your partnership!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_confirmed',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_date' => $this->event->event_date->toDateString(),
            'payment_type' => $this->paymentType,
            'message' => 'Payment confirmed for ' . $this->event->title,
        ];
    }
}
