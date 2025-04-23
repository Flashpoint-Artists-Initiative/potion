<?php

use App\Enums\ArtProjectStatusEnum;
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
        Schema::create('art_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('event_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->text('description');
            $table->string('artist_name')->nullable();
            $table->string('budget_link')->nullable();
            $table->unsignedInteger('min_funding');
            $table->unsignedInteger('max_funding');
            $table->enum('project_status', array_column(ArtProjectStatusEnum::cases(), 'value'))->default(ArtProjectStatusEnum::PendingReview->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_user_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('art_project_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('votes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_user_votes');
    }
};
