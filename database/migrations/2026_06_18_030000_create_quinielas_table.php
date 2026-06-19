<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quinielas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('home_team');
            $table->string('away_team');
            $table->string('home_flag', 16)->nullable();  // emoji flag
            $table->string('away_flag', 16)->nullable();
            $table->dateTime('kickoff_at');
            // scheduled = open for predictions, locked/live = closed, finished = settled
            $table->string('status')->default('scheduled');
            $table->unsignedBigInteger('api_fixture_id')->nullable(); // API-Football fixture
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinielas');
    }
};
