<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->uuid('meeting_id')->primary();
            $table->string('status')->default('Suplanuotas');
            $table->foreignId('secretary_id')->constrained('users', 'user_id');
            $table->string('body_id');
            $table->boolean('is_evote')->default(false);
            $table->dateTime('meeting_date')->nullable();
            $table->dateTime('vote_start')->nullable();
            $table->dateTime('vote_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};

