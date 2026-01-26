<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Payout requests and settlements (create first for foreign key reference)
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique(); // PAY-XXXXXX
            $table->decimal('amount', 10, 2); // Total payout amount
            $table->decimal('processing_fee', 10, 2)->default(0); // Bank/payment gateway fee
            $table->decimal('net_amount', 10, 2); // Final amount transferred
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');

            // Payment details
            $table->string('payment_method')->nullable(); // bank_transfer, upi, etc.
            $table->json('payment_details')->nullable(); // Bank account, UPI ID, etc.
            $table->string('transaction_reference')->nullable(); // External transaction ID

            // Processing info
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Vendor earnings from completed events
        Schema::create('vendor_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_vendor_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_amount', 10, 2); // Agreed price
            $table->decimal('platform_commission', 10, 2); // Commission deducted
            $table->decimal('net_amount', 10, 2); // Amount vendor receives
            $table->enum('status', ['pending', 'available', 'paid'])->default('pending');
            $table->timestamp('available_at')->nullable(); // When funds become available
            $table->foreignId('payout_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->unique(['event_id', 'event_vendor_id']);
            $table->index(['vendor_id', 'status']);
        });

        // Add bank details to vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('bank_details')->nullable()->after('verified_at');
            $table->decimal('pending_balance', 10, 2)->default(0)->after('bank_details');
            $table->decimal('available_balance', 10, 2)->default(0)->after('pending_balance');
            $table->decimal('total_withdrawn', 10, 2)->default(0)->after('available_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['bank_details', 'pending_balance', 'available_balance', 'total_withdrawn']);
        });

        Schema::dropIfExists('payouts');
        Schema::dropIfExists('vendor_earnings');
    }
};
