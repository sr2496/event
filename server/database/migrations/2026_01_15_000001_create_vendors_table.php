<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('category'); // photographer, decorator, caterer, etc.
            $table->string('city');
            $table->integer('service_radius_km')->default(50);
            $table->integer('experience_years')->default(0);
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('identity_proof')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_emergency')->default(true);
            $table->decimal('reliability_score', 3, 2)->default(5.00);
            $table->integer('total_events_completed')->default(0);
            $table->integer('cancellations_count')->default(0);
            $table->integer('no_shows_count')->default(0);
            $table->integer('emergency_accepts_count')->default(0);
            $table->integer('avg_response_minutes')->default(60);
            $table->boolean('backup_ready')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'city', 'is_verified', 'is_active']);
            $table->index('reliability_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
