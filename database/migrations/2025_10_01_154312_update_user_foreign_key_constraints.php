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
        // 1. Update audit_logs: Change from CASCADE to SET NULL
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
        });

        // 2. Update meeting_attendances: Add CASCADE DELETE
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });

        // 3. Update votes: Add SET NULL (preserve voting history)
        Schema::table('votes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        // Make user_id nullable first
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
        
        Schema::table('votes', function (Blueprint $table) {
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
        });

        // 4. Update questions.presenter_id: Add SET NULL
        // Check if foreign key exists before dropping
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign(['presenter_id']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist, that's okay
        }
        
        // Make presenter_id nullable first
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('presenter_id')->nullable()->change();
        });
        
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('presenter_id')->references('user_id')->on('users')->onDelete('set null');
        });

        // 5. Update bodies.chairman_id: Add RESTRICT (prevent deletion)
        Schema::table('bodies', function (Blueprint $table) {
            $table->dropForeign(['chairman_id']);
        });
        
        Schema::table('bodies', function (Blueprint $table) {
            $table->foreign('chairman_id')->references('user_id')->on('users')->onDelete('restrict');
        });

        // 6. Update meetings.secretary_id: Add RESTRICT (prevent deletion)
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['secretary_id']);
        });
        
        Schema::table('meetings', function (Blueprint $table) {
            $table->foreign('secretary_id')->references('user_id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all changes back to original state (no onDelete actions)
        
        // 1. Revert audit_logs
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });

        // 2. Revert meeting_attendances
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->foreign('user_id')->references('user_id')->on('users');
        });

        // 3. Revert votes
        Schema::table('votes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
        
        Schema::table('votes', function (Blueprint $table) {
            $table->foreign('user_id')->references('user_id')->on('users');
        });

        // 4. Revert questions
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['presenter_id']);
        });
        
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('presenter_id')->references('user_id')->on('users');
        });

        // 5. Revert bodies
        Schema::table('bodies', function (Blueprint $table) {
            $table->dropForeign(['chairman_id']);
        });
        
        Schema::table('bodies', function (Blueprint $table) {
            $table->foreign('chairman_id')->references('user_id')->on('users');
        });

        // 6. Revert meetings
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['secretary_id']);
        });
        
        Schema::table('meetings', function (Blueprint $table) {
            $table->foreign('secretary_id')->references('user_id')->on('users');
        });
    }
};
