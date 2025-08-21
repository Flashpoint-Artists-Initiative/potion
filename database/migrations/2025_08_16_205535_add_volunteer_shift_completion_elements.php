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
        Schema::table('shift_signups', function (Blueprint $table) {
            $table->boolean('completed')->nullable();
        });

        Schema::create('volunteer_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('team_id');
            $table->unsignedInteger('user_id');
            $table->integer('hours');
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_signups', function (Blueprint $table) {
            $table->dropColumn('completed');
        });

        Schema::dropIfExists('volunteer_hours');
    }
};
