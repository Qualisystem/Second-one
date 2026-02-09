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
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('abbreviation')->unique();
            $table->string('label');
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            // Make it nullable in case some users (like a super-admin) don't belong to an institution
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });
    }
};