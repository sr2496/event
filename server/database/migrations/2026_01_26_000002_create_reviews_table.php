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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_responded_at')->nullable();
            $table->boolean('is_verified')->default(true); // From completed booking
            $table->boolean('is_visible')->default(true); // Admin can hide
            $table->timestamps();

            // Prevent duplicate reviews
            $table->unique(['event_id', 'vendor_id', 'client_id']);

            // Index for vendor reviews lookup
            $table->index(['vendor_id', 'is_visible', 'created_at']);
        });

        // Add average rating column to vendors table
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('average_rating', 2, 1)->default(0)->after('reliability_score');
            $table->unsignedInteger('total_reviews')->default(0)->after('average_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'total_reviews']);
        });

        Schema::dropIfExists('reviews');
    }
};
