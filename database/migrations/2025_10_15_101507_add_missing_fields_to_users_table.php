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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('employee')->after('password');
            $table->string('avatar')->nullable()->after('role');
            $table->string('position')->nullable()->after('avatar');
            $table->string('phone', 20)->nullable()->after('position');
            $table->string('department')->nullable()->after('phone');
            $table->date('hire_date')->nullable()->after('department');
            #$table->string('google_id')->nullable()->after('hire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'avatar',
                'position',
                'phone',
                'department',
                'hire_date',
                'google_id',
            ]);
        });
    }
};
