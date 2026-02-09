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
        Schema::table('users', function (Blueprint $table) {
            // Add a unique code column. It's nullable for existing users.
            $table->string('code')->unique()->nullable()->after('id');
        });
        Schema::table('applications', function (Blueprint $table) {
            $table->string('user_code')->nullable()->after('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('user_code');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
