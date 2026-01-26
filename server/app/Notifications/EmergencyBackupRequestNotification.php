<?php

namespace App\Notifications;

use App\Models\BackupAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyBackupRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected BackupAssignment $assignment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $event = $this->assignment->event;

        return (new MailMessage)
            ->subject('URGENT: Emergency Backup Request - ' . $event->title)
            ->greeting('Urgent Request, ' . $notifiable->name . '!')
            ->line('You have been selected as a backup vendor for an emergency situation.')
            ->line('The primary vendor is unable to fulfill this booking and we need your help!')
            ->line('**Event Details:**')
            ->line('- Title: ' . $event->title)
            ->line('- Type: ' . $event->type)
            ->line('- Date: ' . $event->event_date->format('F j, Y'))
            ->line('- City: ' . $event->city)
            ->line('- Venue: ' . ($event->venue ?? 'To be confirmed'))
            ->line('**Offered Price:** ' . number_format($this->assignment->offered_price, 2))
            ->line('Please respond as soon as possible. This is time-sensitive.')
            ->action('Accept Emergency Assignment', url('/vendor/emergency/' . $this->assignment->id . '/accept'))
            ->line('Your quick response is greatly appreciated!');
    }

    public function toArray($notifiable): array
    {
        $event = $this->assignment->event;

        return [
            'type' => 'emergency_backup_request',
            'assignment_id' => $this->assignment->id,
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_date' => $event->event_date->toDateString(),
            'offered_price' => $this->assignment->offered_price,
            'message' => 'URGENT: Emergency backup request for ' . $event->title,
        ];
    }
}
