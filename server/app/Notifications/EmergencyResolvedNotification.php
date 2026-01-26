<?php

namespace App\Notifications;

use App\Models\EmergencyRequest;
use App\Models\BackupAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected EmergencyRequest $emergencyRequest,
        protected BackupAssignment $assignment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $event = $this->emergencyRequest->event;
        $backupVendor = $this->assignment->backupVendor;

        return (new MailMessage)
            ->subject('Emergency Resolved - ' . $event->title)
            ->greeting('Good news, ' . $notifiable->name . '!')
            ->line('We have successfully found a replacement vendor for your event.')
            ->line('**Event:** ' . $event->title)
            ->line('**Date:** ' . $event->event_date->format('F j, Y'))
            ->line('**New Vendor:** ' . $backupVendor->business_name)
            ->line('The new vendor has been confirmed and is ready to provide their services.')
            ->line('Your event will proceed as planned. No further action is required from you.')
            ->action('View Event Details', url('/client/events/' . $event->id))
            ->line('Thank you for your patience and trust in our platform!');
    }

    public function toArray($notifiable): array
    {
        $event = $this->emergencyRequest->event;
        $backupVendor = $this->assignment->backupVendor;

        return [
            'type' => 'emergency_resolved',
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_date' => $event->event_date->toDateString(),
            'backup_vendor_name' => $backupVendor->business_name,
            'message' => 'Emergency resolved for ' . $event->title . ' - new vendor: ' . $backupVendor->business_name,
        ];
    }
}
