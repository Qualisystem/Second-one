<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // decimal(precision, scale)
            // Latitude ranges from -90 to +90
            $table->decimal('latitude', 10, 8)->nullable();
            // Longitude ranges from -180 to +180
            $table->decimal('longitude', 11, 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};