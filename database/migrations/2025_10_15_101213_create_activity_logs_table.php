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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action'); // created, updated, completed, etc.
            $table->text('description')->nullable();
            
            // Clés étrangères
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->nullableUuidMorphs('loggable'); // Polymorphic relationship
            
            // Données supplémentaires
            $table->json('properties')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
