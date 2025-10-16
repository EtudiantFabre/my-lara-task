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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Informations de temps
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('duration')->default(0)->comment('Durée en secondes');
            
            // Description et métadonnées
            $table->text('description')->nullable();
            $table->boolean('billable')->default(true);
            
            // Clés étrangères
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('task_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            
            // Index pour les recherches courantes
            $table->index(['user_id', 'start_time']);
            $table->index(['project_id', 'start_time']);
            $table->index(['task_id', 'start_time']);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
