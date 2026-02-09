<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove softDeletes
        foreach (['audits', 'audit_items', 'data_requests'] as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }

        // Ensure cascading delete on audit_id in data_requests
        Schema::table('data_requests', function (Blueprint $table) {
            $table->dropForeign(['audit_id']);
            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
        });

        // Ensure cascading delete on audit_item_id in data_requests
        Schema::table('data_requests', function (Blueprint $table) {
            $table->dropForeign(['audit_item_id']);
            $table->foreign('audit_item_id')->references('id')->on('audit_items')->onDelete('cascade');
        });

        // Ensure cascading delete on audit_id in audit_items
        Schema::table('audit_items', function (Blueprint $table) {
            $table->dropForeign(['audit_id']);
            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
        });

        // Ensure cascading delete on audit_id in file_attachments
        if (DB::getDriverName() === 'mysql') {
            $fkName = null;
            $results = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'file_attachments'
                  AND COLUMN_NAME = 'audit_id'
                  AND REFERENCED_TABLE_NAME = 'audits'
            ");
            if (! empty($results)) {
                $fkName = $results[0]->CONSTRAINT_NAME;
            }

            Schema::table('file_attachments', function (Blueprint $table) use ($fkName) {
                if ($fkName) {
                    $table->dropForeign($fkName);
                }
                $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
            });
        } else {
            // For SQLite or other DBs, just try to drop the foreign key by column name (if it exists)
            Schema::table('file_attachments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['audit_id']);
                } catch (\Exception $e) {
                    // Ignore if not supported or doesn't exist
                }
                $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add softDeletes back
        foreach (['audits', 'audit_items', 'data_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Remove cascade on delete for audit_id in data_requests
        Schema::table('data_requests', function (Blueprint $table) {
            $table->dropForeign(['audit_id']);
            $table->foreign('audit_id')->references('id')->on('audits');
        });

        // Remove cascade on delete for audit_item_id in data_requests
        Schema::table('data_requests', function (Blueprint $table) {
            $table->dropForeign(['audit_item_id']);
            $table->foreign('audit_item_id')->references('id')->on('audit_items');
        });

        // Remove cascade on delete for audit_id in audit_items
        Schema::table('audit_items', function (Blueprint $table) {
            $table->dropForeign(['audit_id']);
            $table->foreign('audit_id')->references('id')->on('audits');
        });

        // Remove cascade on delete for audit_id in file_attachments
        Schema::table('file_attachments', function (Blueprint $table) {
            $table->dropForeign(['audit_id']);
            $table->foreign('audit_id')->references('id')->on('audits');
        });
    }
};
