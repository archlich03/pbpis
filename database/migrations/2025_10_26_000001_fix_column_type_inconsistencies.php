<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes column type inconsistencies across tables:
     * - meeting_id should be uuid(36) everywhere, not string(255)
     * - Ensures all foreign key columns match their referenced columns
     */
    public function up(): void
    {
        // Fix questions.meeting_id - change from string(255) to uuid(36)
        Schema::table('questions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['meeting_id']);
        });
        
        Schema::table('questions', function (Blueprint $table) {
            // Change column type
            $table->uuid('meeting_id')->change();
        });
        
        Schema::table('questions', function (Blueprint $table) {
            // Re-add foreign key
            $table->foreign('meeting_id')->references('meeting_id')->on('meetings')->onDelete('cascade');
        });

        // Fix meeting_attendances.meeting_id - change from string(255) to uuid(36)
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['meeting_id']);
        });
        
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Change column type
            $table->uuid('meeting_id')->change();
        });
        
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Re-add foreign key
            $table->foreign('meeting_id')->references('meeting_id')->on('meetings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert questions.meeting_id back to string
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });
        
        Schema::table('questions', function (Blueprint $table) {
            $table->string('meeting_id')->change();
        });
        
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('meeting_id')->on('meetings')->onDelete('cascade');
        });

        // Revert meeting_attendances.meeting_id back to string
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });
        
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->string('meeting_id')->change();
        });
        
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('meeting_id')->on('meetings')->onDelete('cascade');
        });
    }
};
