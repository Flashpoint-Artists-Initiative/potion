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
        Schema::create('gate_scans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('event_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wristband_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_scans');
    }
};
