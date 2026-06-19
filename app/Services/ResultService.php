<?php

namespace App\Services;

use App\Models\Quiniela;
use App\Models\QuinielaResult;

/**
 * Rebuilds the canonical QuinielaResult from the live match_events timeline the
 * admin enters. Goals drive the score, half-time score and first goal; red card
 * and penalty events drive their respective flags.
 */
class ResultService
{
    public function __construct(private LeaderboardService $leaderboard) {}

    public function rebuildFromEvents(Quiniela $quiniela): QuinielaResult
    {
        $events = $quiniela->events()->orderBy('minute')->orderBy('id')->get();
        $goals = $events->where('type', 'goal');

        $firstGoal = $goals->first();

        $result = QuinielaResult::firstOrNew(['quiniela_id' => $quiniela->id]);
        $result->fill([
            'home_score' => $goals->where('team', 'home')->count(),
            'away_score' => $goals->where('team', 'away')->count(),
            'ht_home' => $goals->where('team', 'home')->where('half', 1)->count(),
            'ht_away' => $goals->where('team', 'away')->where('half', 1)->count(),
            'first_scoring_team' => $firstGoal?->team ?? 'none',
            'first_scorer_player_id' => $firstGoal?->player_id,
            'first_goal_minute' => $firstGoal?->minute,
            'red_card' => $events->where('type', 'red_card')->isNotEmpty(),
            'penalty' => $events->where('type', 'penalty')->isNotEmpty(),
        ]);
        $result->save();

        $this->leaderboard->recalculate($quiniela);

        return $result;
    }
}
