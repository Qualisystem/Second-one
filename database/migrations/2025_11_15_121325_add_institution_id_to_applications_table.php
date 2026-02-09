<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('institution_id')
                ->nullable()
                ->after('id')
                ->constrained('institutions')
                ->onDelete('cascade');
            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeignKeyConstraints();
            $table->dropColumn('institution_id');
        });
    }
};