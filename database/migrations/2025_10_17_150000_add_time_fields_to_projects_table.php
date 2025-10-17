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
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'estimated_time')) {
                $table->decimal('estimated_time', 8, 2)->default(0)->after('deadline');
            }
            if (!Schema::hasColumn('projects', 'time_spent')) {
                $table->decimal('time_spent', 8, 2)->default(0)->after('estimated_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'time_spent')) {
                $table->dropColumn('time_spent');
            }
            if (Schema::hasColumn('projects', 'estimated_time')) {
                $table->dropColumn('estimated_time');
            }
        });
    }
};


