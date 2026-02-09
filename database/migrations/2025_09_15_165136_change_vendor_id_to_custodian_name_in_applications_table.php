<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['vendor_id']);
            
            // Rename the column to be more descriptive
            $table->renameColumn('vendor_id', 'custodian_name');
        });

        // In a separate step, change the column type to a string
        Schema::table('applications', function (Blueprint $table) {
            $table->string('custodian_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Revert the name change
            $table->renameColumn('custodian_name', 'vendor_id');
        });

        // Revert the type and add the foreign key back
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->change();
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }
};