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
        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->string('meeting_id')->constrained('meetings', 'meeting_id');
            $table->string('title');
            $table->string('decision')->nullable();
            $table->foreignId('presenter_id')->constrained('users', 'user_id')->nullable();
            $table->string('type')->default('Nebalsuoti');
            $table->text('summary')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

