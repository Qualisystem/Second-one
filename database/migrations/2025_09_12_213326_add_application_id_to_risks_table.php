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
        if (!Schema::hasTable('applications')) {
            Schema::create('applications', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('url')->nullable();
                $table->text('notes')->nullable();
                $table->string('type')->nullable();
                $table->string('status')->nullable();
                $table->json('logo')->nullable();

                // Foreign keys based on your model's relationships
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};