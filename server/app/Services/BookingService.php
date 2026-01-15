<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Vendor;
use App\Models\EventVendor;
use App\Models\BackupAssignment;
use App\Models\Payment;
use App\Models\VendorAvailability;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingService
{
    // Commission rates
    const ASSURANCE_FEE_PERCENTAGE = 5; // 5% of vendor price
    const BOOKING_COMMISSION_PERCENTAGE = 10; // 10% of vendor price
    const EMERGENCY_COMMISSION_PERCENTAGE = 20; // 20% for emergency bookings
    const MIN_ASSURANCE_FEE = 500; // Minimum assurance fee

    public function createBooking(User $client, Vendor $vendor, array $data): array
    {
        return DB::transaction(function () use ($client, $vendor, $data) {
            // Calculate pricing
            $vendorPrice = $vendor->min_price ?? 10000; // Default for calculation
            $assuranceFee = max(self::MIN_ASSURANCE_FEE, $vendorPrice * (self::ASSURANCE_FEE_PERCENTAGE / 100));
            $platformCommission = $vendorPrice * (self::BOOKING_COMMISSION_PERCENTAGE / 100);
            $advancePayment = $vendorPrice * 0.3; // 30% advance

            // Create event
            $event = Event::create([
                'client_id' => $client->id,
                'title' => $data['title'],
                'type' => $data['type'],
                'event_date' => $data['event_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'venue' => $data['venue'] ?? null,
                'city' => $data['city'],
                'description' => $data['description'] ?? null,
                'expected_guests' => $data['expected_guests'] ?? null,
                'status' => 'pending',
                'total_amount' => $vendorPrice,
                'assurance_fee' => $assuranceFee,
                'platform_commission' => $platformCommission,
            ]);

            // Create primary vendor assignment
            $eventVendor = EventVendor::create([
                'event_id' => $event->id,
                'vendor_id' => $vendor->id,
                'role' => 'primary',
                'status' => 'pending',
                'agreed_price' => $vendorPrice,
                'special_requirements' => $data['special_requirements'] ?? null,
            ]);

            // Create payment records
            // Create payment records (Only Assurance Fee initially)
            Payment::create([
                'event_id' => $event->id,
                'user_id' => $client->id,
                'type' => 'assurance_fee',
                'amount' => $assuranceFee,
                'status' => 'pending',
            ]);



            // Silently assign backup vendors
            $this->assignBackupVendors($event, $eventVendor, $vendor);

            return [
                'event' => $event,
                'payment_details' => [
                    'assurance_fee' => $assuranceFee,
                    'total_to_pay_now' => $assuranceFee, // Only Assurance Fee
                    'advance_payment_amount' => $advancePayment, // Informational
                ],
            ];
        });
    }

    protected function assignBackupVendors(Event $event, EventVendor $eventVendor, Vendor $primaryVendor): void
    {
        // Find suitable backup vendors
        $backupVendors = Vendor::verified()
            ->active()
            ->backupReady()
            ->byCategory($primaryVendor->category)
            ->byCity($primaryVendor->city)
            ->where('id', '!=', $primaryVendor->id)
            ->orderBy('reliability_score', 'desc')
            ->limit(3)
            ->get();

        // If not enough in same city, expand search
        if ($backupVendors->count() < 3) {
            $additionalBackups = Vendor::verified()
                ->active()
                ->backupReady()
                ->byCategory($primaryVendor->category)
                ->where('id', '!=', $primaryVendor->id)
                ->whereNotIn('id', $backupVendors->pluck('id'))
                ->orderBy('reliability_score', 'desc')
                ->limit(3 - $backupVendors->count())
                ->get();

            $backupVendors = $backupVendors->concat($additionalBackups);
        }

        // Create backup assignments (silent - client doesn't know)
        foreach ($backupVendors as $index => $backup) {
            BackupAssignment::create([
                'event_id' => $event->id,
                'event_vendor_id' => $eventVendor->id,
                'backup_vendor_id' => $backup->id,
                'priority_order' => $index + 1,
                'status' => 'standby',
                'offered_price' => $primaryVendor->min_price,
            ]);
        }
    }

    public function confirmPayment(Event $event, array $paymentData): void
    {
        DB::transaction(function () use ($event, $paymentData) {
            // Mark payments as completed
            $event->payments()->where('status', 'pending')->update([
                'status' => 'completed',
                'payment_method' => $paymentData['payment_method'],
                'transaction_id' => $paymentData['transaction_id'],
                'paid_at' => now(),
            ]);

            // Determine what was paid
            $paymentType = $event->payments()->where('transaction_id', $paymentData['transaction_id'])->first()->type ?? 'unknown';

            if ($paymentType === 'assurance_fee') {
                 $event->update(['status' => 'awaiting_vendor']);
            } elseif ($paymentType === 'advance') {
                 $event->update(['status' => 'confirmed']);
                 
                 // Confirm primary vendor
                 $primaryVendor = $event->eventVendors()->primary()->first();
                 if ($primaryVendor) {
                    $primaryVendor->confirm();
                 }
            }
        });
    }

    public function cancelBooking(Event $event, string $reason): void
    {
        DB::transaction(function () use ($event, $reason) {
            // Cancel event
            $event->update([
                'status' => 'cancelled',
            ]);

            // Cancel vendor assignments
            $event->eventVendors()->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Free up vendor availability
            foreach ($event->eventVendors as $eventVendor) {
                VendorAvailability::where('vendor_id', $eventVendor->vendor_id)
                    ->where('date', $event->event_date)
                    ->where('status', 'booked')
                    ->delete();
            }

            // Cancel backup assignments
            $event->backupAssignments()->update(['status' => 'expired']);

            // Handle refunds (simplified - assurance fee is non-refundable)
            $advancePayment = $event->payments()->where('type', 'advance')->completed()->first();
            if ($advancePayment) {
                Payment::create([
                    'event_id' => $event->id,
                    'user_id' => $event->client_id,
                    'type' => 'refund',
                    'amount' => $advancePayment->amount,
                    'status' => 'pending',
                    'notes' => 'Refund for cancelled booking',
                ]);
            }
        });
    }
}
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
