<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['wedding', 'pre_wedding', 'corporate', 'influencer_shoot', 'birthday', 'anniversary', 'other'])->default('other');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('city');
            $table->text('description')->nullable();
            $table->integer('expected_guests')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'emergency'])->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('assurance_fee', 10, 2)->default(0);
            $table->decimal('platform_commission', 10, 2)->default(0);
            $table->boolean('has_emergency')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['event_date', 'status']);
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
