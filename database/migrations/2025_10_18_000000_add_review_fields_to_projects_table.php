<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'reviewed')) {
                $table->boolean('reviewed')->default(false)->after('status');
            }
            if (!Schema::hasColumn('projects', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed');
            }
            if (!Schema::hasColumn('projects', 'reviewed_by')) {
                $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('projects', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('projects', 'reviewed')) {
                $table->dropColumn('reviewed');
            }
        });
    }
};
