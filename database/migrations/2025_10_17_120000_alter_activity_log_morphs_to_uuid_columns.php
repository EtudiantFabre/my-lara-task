<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterActivityLogMorphsToUuidColumns extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name');

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            // Supprime les colonnes id actuelles (bigint) pour recréer en UUID
            if (Schema::connection(config('activitylog.database_connection'))->hasColumn(config('activitylog.table_name'), 'subject_id')) {
                $table->dropColumn('subject_id');
            }
            if (Schema::connection(config('activitylog.database_connection'))->hasColumn(config('activitylog.table_name'), 'causer_id')) {
                $table->dropColumn('causer_id');
            }
        });

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->uuid('subject_id')->nullable();
            $table->uuid('causer_id')->nullable();
            $table->index(['subject_type', 'subject_id'], 'subject_type_subject_id_index');
            $table->index(['causer_type', 'causer_id'], 'causer_type_causer_id_index');
        });
    }

    public function down(): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name');

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            // Revenir à bigint nullable
            if (Schema::connection(config('activitylog.database_connection'))->hasColumn(config('activitylog.table_name'), 'subject_id')) {
                $table->dropColumn('subject_id');
            }
            if (Schema::connection(config('activitylog.database_connection'))->hasColumn(config('activitylog.table_name'), 'causer_id')) {
                $table->dropColumn('causer_id');
            }
        });

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->index(['subject_type', 'subject_id'], 'subject_type_subject_id_index');
            $table->index(['causer_type', 'causer_id'], 'causer_type_causer_id_index');
        });
    }
}
