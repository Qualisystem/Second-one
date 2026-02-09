<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Add the new column after 'risk_owner' for organization
            $table->string('residual_risk_owner')->nullable()->after('risk_owner');
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropColumn('residual_risk_owner');
        });
    }
};