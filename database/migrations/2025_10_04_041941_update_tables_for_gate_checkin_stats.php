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
        // Add data column so we can track additional info about transfers
        Schema::table('ticket_transfers', function (Blueprint $table) {
            $table->json('data')->nullable();
        });

        // Same thing for gate scans
        Schema::table('gate_scans', function (Blueprint $table) {
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_transfers', function (Blueprint $table) {
            $table->dropColumn('data');
        });

        Schema::table('gate_scans', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
