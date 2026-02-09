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
            $table->unsignedTinyInteger('economic_impact')->nullable();
            $table->unsignedTinyInteger('recovery_time')->nullable();
            $table->unsignedTinyInteger('availability_of_alternatives')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['economic_impact', 'recovery_time', 'availability_of_alternatives']);
        });
    }
};
