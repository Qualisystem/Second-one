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
        Schema::rename('applications', 'assets');
        Schema::table('risks', function (Blueprint $table) {
            // Drop the old text column
            $table->dropColumn('asset');
            // Add the new foreign key. It's nullable in case a risk isn't tied to a specific asset.
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('assets', 'applications');
        Schema::table('risks', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
            $table->string('asset')->nullable(); // Re-add the old column on rollback
        });
    }
};
