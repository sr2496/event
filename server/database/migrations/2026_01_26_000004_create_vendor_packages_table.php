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
        Schema::create('vendor_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable()->comment('Original price for showing discount');
            $table->integer('duration_hours')->nullable()->comment('Service duration in hours');
            $table->json('features')->nullable()->comment('Array of included features/items');
            $table->json('deliverables')->nullable()->comment('What the client receives');
            $table->integer('max_revisions')->nullable()->comment('Number of revisions included');
            $table->integer('delivery_days')->nullable()->comment('Estimated delivery time in days');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('bookings_count')->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'is_active']);
            $table->index(['vendor_id', 'sort_order']);
        });

        // Add package_id to events table for tracking which package was selected
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('client_id')->constrained('vendor_packages')->nullOnDelete();
            $table->json('package_snapshot')->nullable()->after('package_id')->comment('Copy of package details at booking time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['package_id', 'package_snapshot']);
        });

        Schema::dropIfExists('vendor_packages');
    }
};
