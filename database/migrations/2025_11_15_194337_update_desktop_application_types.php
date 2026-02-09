<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change all "Desktop" to "Hardware and Computing Infrastructure"
        DB::table('applications')
            ->where('type', 'Telecom Exchange')
            ->update(['type' => 'Data']);
        DB::table('applications')
            ->where('type', 'Power Grid Control Center')
            ->update(['type' => 'Hardware and Computing Infrastructure']);
        DB::table('applications')
            ->where('type', 'SCADA System')
            ->update(['type' => 'Software Systems']);
        DB::table('applications')
            ->where('type', 'SaaS')
            ->update(['type' => 'Human']);
        DB::table('applications')
            ->where('type', 'Desktop')
            ->update(['type' => 'Hardware and Computing Infrastructure']);
        DB::table('applications')
            ->where('type', 'Server')
            ->update(['type' => 'Facilities']);
        DB::table('applications')
            ->where('type', 'Appliance')
            ->update(['type' => 'Facilities']);
        DB::table('applications')
            ->where('type', 'Other')
            ->update(['type' => 'Facilities']);
    }

    public function down(): void
    {
        DB::table('applications')
            ->where('type', 'Data')
            ->update(['type' => 'Telecom Exchange']);
        DB::table('applications')
            ->where('type', 'Hardware and Computing Infrastructure')
            ->update(['type' => 'Power Grid Control Center']);
        DB::table('applications')
            ->where('type', 'Software Systems')
            ->update(['type' => 'SCADA System']);
        DB::table('applications')
            ->where('type', 'Human')
            ->update(['type' => 'SaaS']);
        DB::table('applications')
            ->where('type', 'Hardware and Computing Infrastructure')
            ->update(['type' => 'Desktop']);
        DB::table('applications')
            ->where('type', 'Facilities')
            ->update(['type' => 'Server']);
        DB::table('applications')
            ->where('type', 'Facilities')
            ->update(['type' => 'Appliance']);
        DB::table('applications')
            ->where('type', 'Facilities')
            ->update(['type' => 'Other']);
    }
};