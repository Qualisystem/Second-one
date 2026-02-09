<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns only if they don't already exist.
        if (! Schema::hasColumn('applications', 'custodian')) {
            Schema::table('applications', fn (Blueprint $table) => $table->string('custodian')->nullable());
        }

        if (! Schema::hasColumn('applications', 'location')) {
            Schema::table('applications', fn (Blueprint $table) => $table->string('location')->nullable());
        }

        if (! Schema::hasColumn('applications', 'tier')) {
            Schema::table('applications', fn (Blueprint $table) => $table->unsignedTinyInteger('tier')->nullable());
        }

        if (! Schema::hasColumn('applications', 'impact_users_affected')) {
            Schema::table('applications', fn (Blueprint $table) => $table->unsignedTinyInteger('impact_users_affected')->nullable());
        }

        foreach ([
            'availability_impact',
            'confidentiality_impact',
            'integrity_impact',
            'financial_impact',
            'regulatory_compliance_impact',
            'reputational_damage_impact',
            'health_safety_impact',
            'environmental_impact',
        ] as $column) {
            if (! Schema::hasColumn('applications', $column)) {
                Schema::table('applications', fn (Blueprint $table) => $table->unsignedTinyInteger($column)->nullable());
            }
        }

        if (! Schema::hasColumn('applications', 'cross_sector_dependencies')) {
            Schema::table('applications', fn (Blueprint $table) => $table->unsignedTinyInteger('cross_sector_dependencies')->nullable());
        }

        if (! Schema::hasColumn('applications', 'dependencies')) {
            Schema::table('applications', fn (Blueprint $table) => $table->json('dependencies')->nullable());
        }
    }

    public function down(): void
    {
        // Drop only if they exist (SQLite-safe).
        foreach ([
            'custodian',
            'location',
            'tier',
            'impact_users_affected',
            'availability_impact',
            'confidentiality_impact',
            'integrity_impact',
            'financial_impact',
            'regulatory_compliance_impact',
            'reputational_damage_impact',
            'health_safety_impact',
            'environmental_impact',
            'cross_sector_dependencies',
            'dependencies',
        ] as $column) {
            if (Schema::hasColumn('applications', $column)) {
                Schema::table('applications', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};