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
            $table->softDeletes();
            $table->float('multiplier')->nullable()->change();
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->string('location')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->boolean('multiplier')->nullable()->change();
        });

        Schema::table('shift_types', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
