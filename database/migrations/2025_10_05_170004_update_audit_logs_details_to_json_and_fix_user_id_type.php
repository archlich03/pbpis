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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Change details column from varchar(255) to json if it's not already
            $table->json('details')->nullable()->change();
        });
        
        // Fix ms_graph_tokens user_id from int to bigint
        Schema::table('ms_graph_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Revert details back to string
            $table->string('details', 255)->nullable()->change();
        });
        
        Schema::table('ms_graph_tokens', function (Blueprint $table) {
            $table->integer('user_id')->nullable()->change();
        });
    }
};
