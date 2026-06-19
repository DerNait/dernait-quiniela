<?php

namespace App\Services;

use App\Enums\ScoringCategory;
use App\Models\Prediction;
use App\Models\QuinielaResult;
use App\Models\ScoringRule;
use Illuminate\Support\Collection;

/**
 * Pure scoring engine: given a prediction, the actual result and the points
 * table, it returns the total and a per-category breakdown. It never touches
 * the database, which keeps it trivial to unit test.
 *
 * The x2 wildcard (Prediction::$boost_category) doubles whatever was earned in
 * the chosen category.
 */
class ScoringService
{
    /**
     * @param  Collection<int,ScoringRule>  $rules
     * @return array{total:int, breakdown:array<int,array<string,mixed>>}
     */
    public function compute(Prediction $prediction, QuinielaResult $result, Collection $rules): array
    {
        $rulesByCategory = $rules->keyBy(fn (ScoringRule $r) => $r->category->value);

        $total = 0;
        $breakdown = [];

        foreach (ScoringCategory::cases() as $category) {
            /** @var ScoringRule|null $rule */
            $rule = $rulesByCategory->get($category->value);
            if (! $rule || ! $rule->enabled) {
                continue;
            }

            [$hit, $earned] = $this->evaluate($category, $prediction, $result, $rule->points);

            $boosted = $prediction->boost_category === $category->value;
            if ($boosted) {
                $earned *= 2;
            }

            $total += $earned;
            $breakdown[] = [
                'category' => $category->value,
                'label' => $category->label(),
                'hit' => $hit,
                'points' => $earned,
                'base_points' => $rule->points,
                'boosted' => $boosted,
            ];
        }

        return ['total' => $total, 'breakdown' => $breakdown];
    }

    /**
     * Evaluate a single category.
     *
     * @return array{0:bool, 1:int} [hit, pointsEarned]
     */
    private function evaluate(ScoringCategory $category, Prediction $p, QuinielaResult $r, int $points): array
    {
        $actualFirstTeam = $r->first_scoring_team ?? 'none';

        return match ($category) {
            ScoringCategory::Winner => $this->flat(
                $this->outcome($p->exact_home, $p->exact_away) === $this->outcome($r->home_score, $r->away_score),
                $points
            ),

            ScoringCategory::Btts => $this->flat(
                ($p->exact_home > 0 && $p->exact_away > 0) === ($r->home_score > 0 && $r->away_score > 0),
                $points
            ),

            ScoringCategory::HtWinner => $this->flat(
                $this->outcome($p->ht_home, $p->ht_away) === $this->outcome($r->ht_home, $r->ht_away),
                $points
            ),

            ScoringCategory::FirstTeam => $this->flat(
                $p->first_scoring_team === $actualFirstTeam,
                $points
            ),

            ScoringCategory::GoalDiff => $this->flat(
                ($p->exact_home - $p->exact_away) === ($r->home_score - $r->away_score),
                $points
            ),

            ScoringCategory::TotalGoals => $this->flat(
                ($p->exact_home + $p->exact_away) === ($r->home_score + $r->away_score),
                $points
            ),

            ScoringCategory::HtExact => $this->flat(
                $p->ht_home === $r->ht_home && $p->ht_away === $r->ht_away,
                $points
            ),

            ScoringCategory::Exact => $this->flat(
                $p->exact_home === $r->home_score && $p->exact_away === $r->away_score,
                $points
            ),

            ScoringCategory::FirstScorer => $this->flat(
                $r->first_scorer_player_id !== null
                    ? $p->first_scorer_player_id === $r->first_scorer_player_id
                    : $p->first_scorer_player_id === null && $actualFirstTeam === 'none',
                $points
            ),

            ScoringCategory::RedCard => $this->flat($p->red_card === $r->red_card, $points),

            ScoringCategory::Penalty => $this->flat($p->penalty === $r->penalty, $points),

            ScoringCategory::FirstMinute => $this->minuteProximity($p, $r, $points),
        };
    }

    /** Full points on hit, nothing otherwise. */
    private function flat(bool $hit, int $points): array
    {
        return [$hit, $hit ? $points : 0];
    }

    /**
     * Closeness scoring for the first goal minute: exact = full points,
     * within 2 minutes = 60%, within 5 minutes = 20%. Generates many distinct
     * values, which is exactly what helps break ties.
     */
    private function minuteProximity(Prediction $p, QuinielaResult $r, int $points): array
    {
        if ($r->first_goal_minute === null || $p->first_goal_minute === null) {
            return [false, 0];
        }

        $diff = abs($p->first_goal_minute - $r->first_goal_minute);

        $earned = match (true) {
            $diff === 0 => $points,
            $diff <= 2 => (int) round($points * 0.6),
            $diff <= 5 => (int) round($points * 0.2),
            default => 0,
        };

        return [$earned > 0, $earned];
    }

    /** 'home' | 'draw' | 'away' from a scoreline. */
    private function outcome(int $home, int $away): string
    {
        return match ($home <=> $away) {
            1 => 'home',
            -1 => 'away',
            default => 'draw',
        };
    }
}
