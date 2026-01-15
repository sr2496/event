<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_vendor_id')->constrained('event_vendors')->onDelete('cascade');
            $table->foreignId('backup_vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->integer('priority_order')->default(1); // 1 = first choice backup
            $table->enum('status', ['standby', 'notified', 'accepted', 'rejected', 'expired', 'activated'])->default('standby');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->integer('response_time_minutes')->nullable();
            $table->decimal('offered_price', 10, 2)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['backup_vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_assignments');
    }
};
