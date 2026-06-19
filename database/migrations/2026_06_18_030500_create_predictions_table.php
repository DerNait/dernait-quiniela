<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiniela_id')->constrained()->cascadeOnDelete();

            // Final score -> derives winner, exact, goal_diff, total_goals, btts
            $table->unsignedSmallInteger('exact_home');
            $table->unsignedSmallInteger('exact_away');
            // Half-time score -> derives ht_winner, ht_exact
            $table->unsignedSmallInteger('ht_home');
            $table->unsignedSmallInteger('ht_away');

            $table->string('first_scoring_team'); // 'home'|'away'|'none'
            $table->foreignId('first_scorer_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->boolean('red_card')->default(false);
            $table->boolean('penalty')->default(false);
            $table->unsignedSmallInteger('first_goal_minute')->nullable();

            // The single category the participant chose to double (x2 wildcard)
            $table->string('boost_category')->nullable();

            $table->unsignedSmallInteger('total_points')->default(0);
            $table->json('points_breakdown')->nullable();
            $table->timestamp('submitted_at')->nullable(); // tie-breaker: earliest wins
            $table->timestamps();

            $table->unique(['user_id', 'quiniela_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
