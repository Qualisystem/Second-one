<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('id');
            $table->text('location')->nullable()->after('description');
            $table->text('dependencies')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['code', 'location', 'dependencies']);
        });
    }
};