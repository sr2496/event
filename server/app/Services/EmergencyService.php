<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventVendor;
use App\Models\BackupAssignment;
use App\Models\EmergencyRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Notifications\EmergencyBackupRequestNotification;
use App\Notifications\EmergencyResolvedNotification;

class EmergencyService
{
    const EMERGENCY_COMMISSION_PERCENTAGE = 20;

    public function createEmergencyRequest(
        Event $event,
        EventVendor $eventVendor,
        User $client,
        string $failureReason,
        ?string $proofPath = null
    ): EmergencyRequest {
        return DB::transaction(function () use ($event, $eventVendor, $client, $failureReason, $proofPath) {
            // Mark event as emergency
            $event->markAsEmergency();

            // Mark vendor as failed
            $eventVendor->markAsFailed();

            // Create emergency request
            $emergencyRequest = EmergencyRequest::create([
                'event_id' => $event->id,
                'event_vendor_id' => $eventVendor->id,
                'client_id' => $client->id,
                'status' => 'searching',
                'failure_reason' => $failureReason,
                'proof_file' => $proofPath,
            ]);

            // Notify first backup vendor
            $this->notifyNextBackup($event->id, $eventVendor->id);

            return $emergencyRequest;
        });
    }

    public function notifyNextBackup(int $eventId, int $eventVendorId): ?BackupAssignment
    {
        // Find next available backup in priority order
        $nextBackup = BackupAssignment::where('event_id', $eventId)
            ->where('event_vendor_id', $eventVendorId)
            ->where('status', 'standby')
            ->orderBy('priority_order')
            ->first();

        if ($nextBackup) {
            $nextBackup->notify();

            // Send notification to backup vendor
            if ($nextBackup->backupVendor && $nextBackup->backupVendor->user) {
                $nextBackup->backupVendor->user->notify(new EmergencyBackupRequestNotification($nextBackup));
            }

            return $nextBackup;
        }

        // No more backups available - mark emergency as unresolved
        $emergencyRequest = EmergencyRequest::where('event_id', $eventId)
            ->where('event_vendor_id', $eventVendorId)
            ->latest()
            ->first();

        if ($emergencyRequest && $emergencyRequest->status === 'searching') {
            $emergencyRequest->update(['status' => 'unresolved']);
        }

        return null;
    }

    public function acceptEmergencyAssignment(BackupAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            // Accept the assignment
            $assignment->accept();

            // Mark as activated
            $assignment->update(['status' => 'activated']);

            // Create new event vendor record for emergency replacement
            $eventVendor = EventVendor::create([
                'event_id' => $assignment->event_id,
                'vendor_id' => $assignment->backup_vendor_id,
                'role' => 'emergency_replacement',
                'status' => 'confirmed',
                'agreed_price' => $assignment->offered_price,
                'confirmed_at' => now(),
            ]);

            // Update emergency request
            $emergencyRequest = EmergencyRequest::where('event_id', $assignment->event_id)
                ->where('event_vendor_id', $assignment->event_vendor_id)
                ->latest()
                ->first();

            if ($emergencyRequest) {
                $emergencyRequest->assignBackup($assignment->backup_vendor_id);
                $emergencyRequest->resolve('Backup vendor accepted: ' . $assignment->backupVendor->business_name);

                // Notify client that emergency has been resolved
                $event = $assignment->event;
                if ($event->client) {
                    $event->client->notify(new EmergencyResolvedNotification($emergencyRequest, $assignment));
                }
            }

            // Expire other pending backup assignments for this event
            BackupAssignment::where('event_id', $assignment->event_id)
                ->where('event_vendor_id', $assignment->event_vendor_id)
                ->where('id', '!=', $assignment->id)
                ->whereIn('status', ['standby', 'notified'])
                ->update(['status' => 'expired']);

            // Create emergency commission payment
            $event = $assignment->event;
            $emergencyCommission = $assignment->offered_price * (self::EMERGENCY_COMMISSION_PERCENTAGE / 100);

            Payment::create([
                'event_id' => $event->id,
                'user_id' => $event->client_id,
                'event_vendor_id' => $eventVendor->id,
                'type' => 'emergency_commission',
                'amount' => $emergencyCommission,
                'status' => 'pending',
                'notes' => 'Emergency replacement commission',
            ]);
        });
    }

    public function getAvailableBackups(Event $event, EventVendor $failedVendor): \Illuminate\Support\Collection
    {
        $failedVendorModel = $failedVendor->vendor;

        return \App\Models\Vendor::verified()
            ->active()
            ->backupReady()
            ->byCategory($failedVendorModel->category)
            ->where('id', '!=', $failedVendorModel->id)
            ->whereDoesntHave('availability', function ($query) use ($event) {
                $query->where('date', $event->event_date)
                      ->whereIn('status', ['booked', 'blocked']);
            })
            ->orderByRaw("CASE WHEN city = ? THEN 0 ELSE 1 END", [$event->city])
            ->orderBy('reliability_score', 'desc')
            ->limit(10)
            ->get();
    }
}
