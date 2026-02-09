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
        Schema::table('risks', function (Blueprint $table) {
            // Add the 'transfer_to' column if it doesn't already exist.
            if (!Schema::hasColumn('risks', 'transfer_to')) {
                $table->string('transfer_to')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            if (Schema::hasColumn('risks', 'transfer_to')) {
                $table->dropColumn('transfer_to');
            }
        });
    }
};