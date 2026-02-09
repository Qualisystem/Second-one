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
        Schema::table('risks', function (Blueprint $table) {
            // Using Schema::hasColumn to prevent errors if a column already exists.
            if (!Schema::hasColumn('risks', 'asset')) {
                $table->string('asset')->nullable();
            }
            if (!Schema::hasColumn('risks', 'threat')) {
                $table->string('threat')->nullable();
            }
            if (!Schema::hasColumn('risks', 'vulnerability')) {
                $table->string('vulnerability')->nullable();
            }
            if (!Schema::hasColumn('risks', 'risk_description')) {
                $table->text('risk_description')->nullable(); // Using text for longer descriptions
            }
            if (!Schema::hasColumn('risks', 'existing_controls')) {
                $table->string('existing_controls')->nullable();
            }
            if (!Schema::hasColumn('risks', 'status_of_existing_controls')) {
                $table->string('status_of_existing_controls')->nullable();
            }
            if (!Schema::hasColumn('risks', 'inherent_likelihood')) {
                $table->integer('inherent_likelihood')->nullable();
            }
            if (!Schema::hasColumn('risks', 'inherent_impact')) {
                $table->integer('inherent_impact')->nullable();
            }
            if (!Schema::hasColumn('risks', 'inherent_risk')) {
                $table->integer('inherent_risk')->nullable();
            }
            if (!Schema::hasColumn('risks', 'risk_owner')) {
                $table->string('risk_owner')->nullable();
            }
            if (!Schema::hasColumn('risks', 'treatment_options')) {
                $table->string('treatment_options')->nullable();
            }
            if (!Schema::hasColumn('risks', 'treatment_description')) {
                $table->text('treatment_description')->nullable();
            }
            if (!Schema::hasColumn('risks', 'acceptable_control_from_any_standard')) {
                $table->string('acceptable_control_from_any_standard')->nullable();
            }
            if (!Schema::hasColumn('risks', 'responsible')) {
                $table->string('responsible')->nullable();
            }
            if (!Schema::hasColumn('risks', 'implementation_status')) {
                $table->string('implementation_status')->nullable();
            }
            if (!Schema::hasColumn('risks', 'comment_on_closure')) {
                $table->text('comment_on_closure')->nullable();
            }
            if (!Schema::hasColumn('risks', 'residual_likelihood')) {
                $table->integer('residual_likelihood')->nullable();
            }
            if (!Schema::hasColumn('risks', 'residual_impact')) {
                $table->integer('residual_impact')->nullable();
            }
            if (!Schema::hasColumn('risks', 'residual_risk')) {
                $table->integer('residual_risk')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Drop columns if they exist
            $columns_to_drop = [
                'asset', 'threat', 'vulnerability', 'risk_description', 'existing_controls',
                'status_of_existing_controls', 'inherent_likelihood', 'inherent_impact',
                'inherent_risk', 'risk_owner', 'treatment_options', 'treatment_description',
                'acceptable_control_from_any_standard', 'responsible', 'implementation_status',
                'comment_on_closure', 'residual_likelihood', 'residual_impact', 'residual_risk'
            ];
            foreach ($columns_to_drop as $column) {
                if (Schema::hasColumn('risks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};