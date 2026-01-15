<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['primary', 'backup', 'emergency_replacement'])->default('primary');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'failed', 'replaced', 'completed'])->default('pending');
            $table->decimal('agreed_price', 10, 2);
            $table->decimal('advance_paid', 10, 2)->default(0);
            $table->decimal('final_payment', 10, 2)->default(0);
            $table->text('special_requirements')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'role']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_vendors');
    }
};
