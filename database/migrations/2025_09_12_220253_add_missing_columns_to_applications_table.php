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
        Schema::table('applications', function (Blueprint $table) {
            // List of columns to add with their types
            $columns = [
                'name' => ['type' => 'string'],
                'description' => ['type' => 'text', 'nullable' => true],
                'url' => ['type' => 'string', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'type' => ['type' => 'string', 'nullable' => true],
                'status' => ['type' => 'string', 'nullable' => true],
                'logo' => ['type' => 'json', 'nullable' => true],
                'owner_id' => ['type' => 'foreignId', 'constrained' => 'users', 'nullable' => true],
            ];

            foreach ($columns as $columnName => $config) {
                if (!Schema::hasColumn('applications', $columnName)) {
                    $column = $table->{$config['type']}($columnName);
                    
                    if ($config['type'] === 'foreignId' && isset($config['constrained'])) {
                        $column->constrained($config['constrained'])->nullOnDelete();
                    }

                    if (isset($config['nullable']) && $config['nullable']) {
                        $column->nullable();
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $columnsToDrop = ['name', 'description', 'url', 'notes', 'type', 'status', 'logo', 'owner_id'];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    // Special handling for foreign keys
                    if ($column === 'owner_id') {
                        $table->dropForeign(['owner_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};