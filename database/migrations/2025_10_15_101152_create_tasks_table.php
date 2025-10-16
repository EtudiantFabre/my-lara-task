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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimated_time', 8, 2)->comment('Estimated time in hours');
            $table->decimal('time_spent', 8, 2)->default(0)->comment('Time spent in hours');
            $table->decimal('progress', 5, 2)->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])->default('not_started');
            $table->date('due_date')->nullable();
            
            // Clés étrangères
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('assigned_to')->constrained('users');
            $table->foreignUuid('created_by')->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
