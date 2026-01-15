<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['cancellation', 'no_show', 'late_arrival', 'poor_service', 'policy_violation', 'other']);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('score_penalty', 4, 2)->default(0);
            $table->text('reason');
            $table->enum('status', ['pending', 'applied', 'waived', 'appealed'])->default('pending');
            $table->text('appeal_reason')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
