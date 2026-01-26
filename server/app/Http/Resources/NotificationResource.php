<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getNotificationType(),
            'data' => $this->data,
            'read_at' => $this->read_at?->toISOString(),
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at->toISOString(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Get a clean notification type name.
     */
    protected function getNotificationType(): string
    {
        // Convert "App\Notifications\BookingCreatedNotification" to "booking_created"
        $className = class_basename($this->type);
        $name = str_replace('Notification', '', $className);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
