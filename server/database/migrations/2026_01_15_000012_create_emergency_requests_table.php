<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_vendor_id')->constrained('event_vendors')->onDelete('cascade'); // failed vendor
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'searching', 'backup_found', 'resolved', 'unresolved', 'cancelled'])->default('pending');
            $table->text('failure_reason');
            $table->string('proof_file')->nullable(); // screenshot/message proof
            $table->timestamp('proof_verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_backup_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->timestamp('backup_assigned_at')->nullable();
            $table->integer('resolution_time_minutes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_requests');
    }
};
