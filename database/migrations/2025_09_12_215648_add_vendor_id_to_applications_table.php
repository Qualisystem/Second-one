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
        Schema::table('applications', function (Blueprint $table) {
            // Check if the column doesn't exist before adding it
            if (!Schema::hasColumn('applications', 'vendor_id')) {
                // This adds the vendor_id column and links it to the vendors table
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Check if the column exists before trying to drop it
            if (Schema::hasColumn('applications', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};