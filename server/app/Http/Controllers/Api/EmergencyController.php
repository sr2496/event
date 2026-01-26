<?php

namespace App\Http\Controllers\Api;

/**
 * @group Emergency
 *
 * APIs for handling emergency situations when vendors fail to show up. Includes triggering emergencies and backup vendor responses.
 * @authenticated
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventVendor;
use App\Models\EmergencyRequest;
use App\Models\BackupAssignment;
use App\Services\EmergencyService;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    protected EmergencyService $emergencyService;

    public function __construct(EmergencyService $emergencyService)
    {
        $this->emergencyService = $emergencyService;
    }

    public function triggerEmergency(Request $request, $eventId)
    {
        $event = Event::where('client_id', $request->user()->id)
            ->findOrFail($eventId);

        if (!$event->canTriggerEmergency()) {
            return response()->json([
                'message' => 'Cannot trigger emergency for this event',
            ], 422);
        }

        $validated = $request->validate([
            'event_vendor_id' => ['required', 'exists:event_vendors,id'],
            'failure_reason' => ['required', 'string'],
            'proof_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $eventVendor = EventVendor::where('event_id', $event->id)
            ->findOrFail($validated['event_vendor_id']);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('emergency-proofs', 'public');
        }

        $emergencyRequest = $this->emergencyService->createEmergencyRequest(
            $event,
            $eventVendor,
            $request->user(),
            $validated['failure_reason'],
            $proofPath
        );

        return response()->json([
            'message' => 'Emergency request created. We are searching for a backup vendor.',
            'emergency_request' => $emergencyRequest,
        ], 201);
    }

    public function status(Request $request, $eventId)
    {
        $event = Event::where('client_id', $request->user()->id)
            ->findOrFail($eventId);

        $emergencyRequest = $event->emergencyRequests()
            ->with(['assignedBackup.user', 'eventVendor.vendor'])
            ->latest()
            ->first();

        if (!$emergencyRequest) {
            return response()->json([
                'message' => 'No emergency request found for this event',
            ], 404);
        }

        return response()->json([
            'emergency_request' => $emergencyRequest,
            'event' => new EventResource($event->load(['eventVendors.vendor'])),
        ]);
    }

    // Vendor: View emergency requests
    public function vendorEmergencyRequests(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $assignments = BackupAssignment::with(['event', 'eventVendor.vendor'])
            ->where('backup_vendor_id', $vendor->id)
            ->whereIn('status', ['notified', 'standby'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $assignments,
        ]);
    }

    // Vendor: Accept emergency request
    public function acceptEmergency(Request $request, $assignmentId)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $assignment = BackupAssignment::where('backup_vendor_id', $vendor->id)
            ->where('status', 'notified')
            ->findOrFail($assignmentId);

        $this->emergencyService->acceptEmergencyAssignment($assignment);

        return response()->json([
            'message' => 'Emergency assignment accepted successfully',
            'assignment' => $assignment->fresh(['event']),
        ]);
    }

    // Vendor: Reject emergency request
    public function rejectEmergency(Request $request, $assignmentId)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $assignment = BackupAssignment::where('backup_vendor_id', $vendor->id)
            ->where('status', 'notified')
            ->findOrFail($assignmentId);

        $assignment->reject($validated['reason']);

        // Notify next backup
        $this->emergencyService->notifyNextBackup($assignment->event_id, $assignment->event_vendor_id);

        return response()->json([
            'message' => 'Emergency assignment rejected',
        ]);
    }
}
