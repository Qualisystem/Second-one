<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_geolocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('label')->nullable(); // e.g., "Main Office", "Data Center"
            $table->string('raw_value')->nullable(); // store original value (Google Plus Code, etc.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_geolocations');
    }
};