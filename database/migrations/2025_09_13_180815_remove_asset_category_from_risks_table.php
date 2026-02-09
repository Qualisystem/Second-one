<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Check if the column exists before trying to drop it
            if (Schema::hasColumn('risks', 'asset_category')) {
                $table->dropColumn('asset_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Re-add the column on rollback for safety
            if (!Schema::hasColumn('risks', 'asset_category')) {
                $table->string('asset_category')->nullable();
            }
        });
    }
};