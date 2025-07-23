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
        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->string('meeting_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->timestamps();
            
            // Foreign key constraint for meeting_id
            $table->foreign('meeting_id')->references('meeting_id')->on('meetings')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate attendance records
            $table->unique(['meeting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
    }
};
