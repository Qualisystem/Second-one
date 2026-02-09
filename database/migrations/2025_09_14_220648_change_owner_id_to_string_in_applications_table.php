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
            $table->dropForeign(['owner_id']);
            
            // Rename the column and change its type
            $table->renameColumn('owner_id', 'owner_name');
        });

        // Separate step to change the column type to string
        Schema::table('applications', function (Blueprint $table) {
            $table->string('owner_name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Revert the name change
            $table->renameColumn('owner_name', 'owner_id');
        });

        // Revert the type and add the foreign key back
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->change();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};