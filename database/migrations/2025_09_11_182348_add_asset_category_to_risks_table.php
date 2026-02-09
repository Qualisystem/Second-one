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
            // Add the missing column. ->after('code') is optional but places it neatly in the table structure.
            $table->string('asset_category')->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropColumn('asset_category');
        });
    }
};
