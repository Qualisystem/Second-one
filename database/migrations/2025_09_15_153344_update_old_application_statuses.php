<?php

use App\Enums\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('applications')
            ->where('status', 'Approved')
            ->update(['status' => ApplicationStatus::ACTIVE->value]);
        DB::table('applications')
            ->where('status', 'Limited')
            ->update(['status' => ApplicationStatus::CANDIDATE->value]);

        // Map 'Rejected' and 'Expired' to 'Retired (No longer in use)'
        DB::table('applications')
            ->whereIn('status', ['Rejected', 'Expired'])
            ->update(['status' => ApplicationStatus::RETIRED->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('applications')
            ->where('status', ApplicationStatus::ACTIVE->value)
            ->update(['status' => 'Approved']);

        // Revert 'Candidate (Under review)' back to 'Limited'
        DB::table('applications')
            ->where('status', ApplicationStatus::CANDIDATE->value)
            ->update(['status' => 'Limited']);

        // Revert 'Retired (No longer in use)' back to 'Rejected'.
        // Note: This is an approximation, as the original could have been 'Expired'.
        DB::table('applications')
            ->where('status', ApplicationStatus::RETIRED->value)
            ->update(['status' => 'Rejected']);
    }
};
