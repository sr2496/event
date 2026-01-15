<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reliability_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action', [
                'event_completed',
                'event_cancelled_by_vendor',
                'event_cancelled_by_client',
                'no_show',
                'emergency_accepted',
                'emergency_rejected',
                'late_response',
                'early_response',
                'backup_activated',
                'positive_feedback',
                'negative_feedback'
            ]);
            $table->decimal('score_change', 4, 2)->default(0);
            $table->decimal('score_before', 3, 2);
            $table->decimal('score_after', 3, 2);
            $table->text('details')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reliability_logs');
    }
};
