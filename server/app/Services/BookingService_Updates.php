<?php

namespace App\Services;

// ... (existing imports)

    // ... (append to class)

    public function vendorAccept(Event $event): void
    {
        DB::transaction(function () use ($event) {
            // Check status
            if ($event->status !== 'awaiting_vendor') {
                throw new \Exception('Event is not waiting for vendor acceptance');
            }

            // Update status to approved (waiting for advance)
            $event->update(['status' => 'approved']);

            // Create Advance Payment record
            $vendorPrice = $event->total_amount;
            $advancePayment = $vendorPrice * 0.3;

            Payment::create([
                'event_id' => $event->id,
                'user_id' => $event->client_id,
                'event_vendor_id' => $event->eventVendors()->primary()->first()->id,
                'type' => 'advance',
                'amount' => $advancePayment,
                'status' => 'pending',
            ]);
        });
    }

    public function vendorReject(Event $event, string $reason): void
    {
        DB::transaction(function () use ($event, $reason) {
            // Cancel event
            $event->update(['status' => 'rejected']);

            // Update vendor status
            $event->eventVendors()->update([
                'status' => 'rejected',
                'cancellation_reason' => $reason
            ]);

            // Refund Assurance Fee (Simulated)
            $assurancePayment = $event->payments()->where('type', 'assurance_fee')->completed()->first();
            if ($assurancePayment) {
                 Payment::create([
                    'event_id' => $event->id,
                    'user_id' => $event->client_id,
                    'type' => 'refund',
                    'amount' => $assurancePayment->amount,
                    'status' => 'pending',
                    'notes' => 'Refund for vendor rejection',
                ]);
            }
        });
    }
}
