<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration consolidates and fixes database schema issues:
     * 1. Fixes incorrect foreign key relationships
     * 2. Removes unnecessary columns
     * 3. Adds proper indexes
     * 4. Ensures data type consistency
     */
    public function up(): void
    {
        // Fix meetings.body_id foreign key relationship
        Schema::table('meetings', function (Blueprint $table) {
            // Change body_id from string to uuid and add proper foreign key
            $table->uuid('body_id')->change();
            $table->foreign('body_id')->references('body_id')->on('bodies')->onDelete('cascade');
        });

        // Fix questions.meeting_id foreign key (it was using constrained incorrectly)
        Schema::table('questions', function (Blueprint $table) {
            // Check if foreign key exists before dropping
            // The constrained() method may not have created the foreign key properly
            // So we'll just add the proper foreign key constraint
            $table->foreign('meeting_id')->references('meeting_id')->on('meetings')->onDelete('cascade');
        });

        // Remove unnecessary columns from users table
        Schema::table('users', function (Blueprint $table) {
            // isLoggedIn is session state - handled by sessions table
            $table->dropColumn('isLoggedIn');
            
            // last_login is tracked in audit_logs
            $table->dropColumn('last_login');
        });

        // Add indexes for better query performance
        Schema::table('votes', function (Blueprint $table) {
            $table->index(['question_id', 'user_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index('meeting_id');
            $table->index('presenter_id');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->index('body_id');
            $table->index('secretary_id');
            $table->index('status');
            $table->index('meeting_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex(['body_id']);
            $table->dropIndex(['secretary_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['meeting_date']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['meeting_id']);
            $table->dropIndex(['presenter_id']);
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['question_id', 'user_id']);
        });

        // Restore removed columns
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('isLoggedIn')->default(false);
            $table->dateTime('last_login')->default(now());
        });

        // Revert questions foreign key
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });

        // Revert meetings.body_id
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['body_id']);
            $table->string('body_id')->change();
        });
    }
};
