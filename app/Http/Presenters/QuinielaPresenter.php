<?php

namespace App\Http\Presenters;

use App\Models\Prediction;
use App\Models\Quiniela;
use App\Models\User;

/**
 * Builds the JSON shapes the Vue frontend consumes. Centralised here so the
 * public, detail and admin endpoints stay consistent.
 */
class QuinielaPresenter
{
    /** Card-level summary for the home list. */
    public static function summary(Quiniela $q): array
    {
        return [
            'id' => $q->id,
            'name' => $q->name,
            'home_team' => $q->home_team,
            'away_team' => $q->away_team,
            'home_flag' => $q->home_flag,
            'away_flag' => $q->away_flag,
            'kickoff_at' => $q->kickoff_at?->toIso8601String(),
            'status' => $q->status,
            'is_open' => $q->isOpen(),
            'predictions_count' => $q->predictions()->count(),
        ];
    }

    /** Full detail: rules, roster, my prediction and (once closed) the result. */
    public static function detail(Quiniela $q, ?User $user): array
    {
        $q->loadMissing(['scoringRules', 'players', 'result.firstScorer']);

        $mine = $user
            ? $q->predictions()->where('user_id', $user->id)->with('firstScorer')->first()
            : null;

        return array_merge(self::summary($q), [
            'rules' => $q->scoringRules
                ->sortBy('points')
                ->values()
                ->map(fn ($r) => [
                    'category' => $r->category->value,
                    'label' => $r->category->label(),
                    'hint' => $r->category->hint(),
                    'points' => $r->points,
                    'enabled' => $r->enabled,
                ])->all(),
            'roster' => [
                'home' => self::players($q, 'home'),
                'away' => self::players($q, 'away'),
            ],
            'my_prediction' => $mine ? self::prediction($mine) : null,
            'result' => $q->status === 'scheduled' ? null : self::result($q),
        ]);
    }

    private static function players(Quiniela $q, string $team): array
    {
        return $q->players
            ->where('team', $team)
            ->values()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'number' => $p->number,
                'kind' => $p->kind,
            ])->all();
    }

    public static function prediction(Prediction $p): array
    {
        return [
            'id' => $p->id,
            'exact_home' => $p->exact_home,
            'exact_away' => $p->exact_away,
            'ht_home' => $p->ht_home,
            'ht_away' => $p->ht_away,
            'first_scoring_team' => $p->first_scoring_team,
            'first_scorer_player_id' => $p->first_scorer_player_id,
            'first_scorer_name' => $p->firstScorer?->name,
            'red_card' => $p->red_card,
            'penalty' => $p->penalty,
            'first_goal_minute' => $p->first_goal_minute,
            'boost_category' => $p->boost_category,
            'total_points' => $p->total_points,
            'points_breakdown' => $p->points_breakdown,
            'submitted_at' => $p->submitted_at?->toIso8601String(),
        ];
    }

    /** Live state: scoreboard + event feed. */
    public static function live(Quiniela $q): array
    {
        $q->loadMissing(['events.player']);

        return [
            'id' => $q->id,
            'status' => $q->status,
            'home_team' => $q->home_team,
            'away_team' => $q->away_team,
            'home_flag' => $q->home_flag,
            'away_flag' => $q->away_flag,
            'result' => self::result($q),
            'events' => $q->events
                ->sortBy([['minute', 'asc'], ['id', 'asc']])
                ->values()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'team' => $e->team,
                    'minute' => $e->minute,
                    'half' => $e->half,
                    'player_name' => $e->player?->name,
                ])->all(),
        ];
    }

    private static function result(Quiniela $q): array
    {
        $r = $q->result()->with('firstScorer')->first();

        if (! $r) {
            return [
                'home_score' => 0, 'away_score' => 0,
                'ht_home' => 0, 'ht_away' => 0,
                'first_scoring_team' => null, 'first_scorer_player_id' => null,
                'first_scorer_name' => null, 'first_goal_minute' => null,
                'red_card' => false, 'penalty' => false,
            ];
        }

        return [
            'home_score' => $r->home_score,
            'away_score' => $r->away_score,
            'ht_home' => $r->ht_home,
            'ht_away' => $r->ht_away,
            'first_scoring_team' => $r->first_scoring_team,
            'first_scorer_player_id' => $r->first_scorer_player_id,
            'first_scorer_name' => $r->firstScorer?->name,
            'first_goal_minute' => $r->first_goal_minute,
            'red_card' => $r->red_card,
            'penalty' => $r->penalty,
        ];
    }
}
