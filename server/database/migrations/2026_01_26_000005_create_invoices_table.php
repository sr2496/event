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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Invoice recipient');
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->enum('type', ['proforma', 'booking', 'receipt', 'vendor_earning'])->default('booking');
            $table->string('title');
            $table->text('description')->nullable();

            // Amounts
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_description')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('INR');

            // Dates
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Status
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled', 'refunded'])->default('draft');

            // Additional info
            $table->text('notes')->nullable();
            $table->json('line_items')->nullable()->comment('Detailed breakdown of charges');
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable();

            // PDF storage
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->timestamps();

            $table->index(['event_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['vendor_id', 'type']);
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
