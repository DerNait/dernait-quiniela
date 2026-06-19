<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per quiniela holding the actual outcome the admin enters live.
        Schema::create('quiniela_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiniela_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('home_score')->default(0);
            $table->unsignedSmallInteger('away_score')->default(0);
            $table->unsignedSmallInteger('ht_home')->default(0);
            $table->unsignedSmallInteger('ht_away')->default(0);
            $table->string('first_scoring_team')->nullable(); // 'home'|'away'|'none'
            $table->foreignId('first_scorer_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedSmallInteger('first_goal_minute')->nullable();
            $table->boolean('red_card')->default(false);
            $table->boolean('penalty')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiniela_results');
    }
};
