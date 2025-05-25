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
        Schema::table('shifts', function (Blueprint $table) {
            $table->float('multiplier')->change();
            $table->float('length')->change();
            $table->float('num_spots')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->float('multiplier')->nullable()->change();
            $table->float('length')->nullable()->change();
            $table->float('num_spots')->nullable()->change();
        });
    }
};
