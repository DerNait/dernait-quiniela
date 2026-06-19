<?php

namespace Tests\Unit;

use App\Enums\ScoringCategory;
use App\Models\Prediction;
use App\Models\QuinielaResult;
use App\Models\ScoringRule;
use App\Services\ScoringService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ScoringServiceTest extends TestCase
{
    private ScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScoringService;
    }

    /** All categories enabled at their default points. */
    private function defaultRules(): Collection
    {
        return collect(ScoringCategory::cases())->map(fn (ScoringCategory $c) => new ScoringRule([
            'category' => $c->value,
            'points' => $c->defaultPoints(),
            'enabled' => true,
        ]));
    }

    private function makePrediction(array $attributes = []): Prediction
    {
        return new Prediction(array_merge([
            'exact_home' => 2, 'exact_away' => 1,
            'ht_home' => 1, 'ht_away' => 0,
            'first_scoring_team' => 'home',
            'first_scorer_player_id' => null,
            'red_card' => false, 'penalty' => false,
            'first_goal_minute' => 20,
            'boost_category' => null,
        ], $attributes));
    }

    private function actualResult(array $attributes = []): QuinielaResult
    {
        return new QuinielaResult(array_merge([
            'home_score' => 2, 'away_score' => 1,
            'ht_home' => 1, 'ht_away' => 0,
            'first_scoring_team' => 'home',
            'first_scorer_player_id' => null,
            'first_goal_minute' => 20,
            'red_card' => false, 'penalty' => false,
        ], $attributes));
    }

    private function pointsFor(array $breakdown, string $category): int
    {
        foreach ($breakdown as $row) {
            if ($row['category'] === $category) {
                return $row['points'];
            }
        }
        return -1;
    }

    public function test_perfect_prediction_scores_every_category(): void
    {
        // 2-1, HT 1-0, home scores first via player #5 at minute 20, no cards.
        $out = $this->service->compute(
            $this->makePrediction(['first_scorer_player_id' => 5]),
            $this->actualResult(['first_scorer_player_id' => 5]),
            $this->defaultRules()
        );

        // winner 3 + btts 2 + ht_winner 2 + first_team 3 + goal_diff 3
        // + total 4 + ht_exact 5 + exact 6 + first_scorer 10 + red 2 + penalty 2 + minute 5
        $this->assertSame(47, $out['total']);
    }

    public function test_wrong_score_but_right_winner_only_scores_winner_family(): void
    {
        // Predict 4-0, actual 2-1: winner(home) yes, btts no, goal_diff no,
        // total no, exact no. HT 1-0 matches both.
        $out = $this->service->compute(
            $this->makePrediction(['exact_home' => 4, 'exact_away' => 0, 'first_goal_minute' => 99]),
            $this->actualResult(),
            $this->defaultRules()
        );

        $this->assertSame(3, $this->pointsFor($out['breakdown'], 'winner'));
        $this->assertSame(0, $this->pointsFor($out['breakdown'], 'btts'));
        $this->assertSame(0, $this->pointsFor($out['breakdown'], 'goal_diff'));
        $this->assertSame(0, $this->pointsFor($out['breakdown'], 'total_goals'));
        $this->assertSame(0, $this->pointsFor($out['breakdown'], 'exact'));
        // HT 1-0 still right -> ht_winner + ht_exact
        $this->assertSame(2, $this->pointsFor($out['breakdown'], 'ht_winner'));
        $this->assertSame(5, $this->pointsFor($out['breakdown'], 'ht_exact'));
    }

    public function test_first_scorer_matches_by_player_id(): void
    {
        $out = $this->service->compute(
            $this->makePrediction(['first_scorer_player_id' => 7]),
            $this->actualResult(['first_scorer_player_id' => 7]),
            $this->defaultRules()
        );
        $this->assertSame(10, $this->pointsFor($out['breakdown'], 'first_scorer'));

        $miss = $this->service->compute(
            $this->makePrediction(['first_scorer_player_id' => 7]),
            $this->actualResult(['first_scorer_player_id' => 9]),
            $this->defaultRules()
        );
        $this->assertSame(0, $this->pointsFor($miss['breakdown'], 'first_scorer'));
    }

    public function test_no_goals_first_scorer_rewards_none_prediction(): void
    {
        $goalless = $this->actualResult([
            'home_score' => 0, 'away_score' => 0, 'ht_home' => 0, 'ht_away' => 0,
            'first_scoring_team' => 'none', 'first_scorer_player_id' => null,
            'first_goal_minute' => null,
        ]);

        $out = $this->service->compute(
            $this->makePrediction([
                'exact_home' => 0, 'exact_away' => 0, 'ht_home' => 0, 'ht_away' => 0,
                'first_scoring_team' => 'none', 'first_scorer_player_id' => null,
                'first_goal_minute' => null,
            ]),
            $goalless,
            $this->defaultRules()
        );

        $this->assertSame(10, $this->pointsFor($out['breakdown'], 'first_scorer'));
        $this->assertSame(3, $this->pointsFor($out['breakdown'], 'first_team'));
    }

    public function test_minute_proximity_tiers(): void
    {
        $rules = $this->defaultRules(); // first_minute default points = 5
        $actual = $this->actualResult(['first_goal_minute' => 30]);

        $exact = $this->service->compute($this->makePrediction(['first_goal_minute' => 30]), $actual, $rules);
        $within2 = $this->service->compute($this->makePrediction(['first_goal_minute' => 32]), $actual, $rules);
        $within5 = $this->service->compute($this->makePrediction(['first_goal_minute' => 35]), $actual, $rules);
        $far = $this->service->compute($this->makePrediction(['first_goal_minute' => 60]), $actual, $rules);

        $this->assertSame(5, $this->pointsFor($exact['breakdown'], 'first_minute'));
        $this->assertSame(3, $this->pointsFor($within2['breakdown'], 'first_minute'));
        $this->assertSame(1, $this->pointsFor($within5['breakdown'], 'first_minute'));
        $this->assertSame(0, $this->pointsFor($far['breakdown'], 'first_minute'));
    }

    public function test_boost_doubles_the_chosen_category_only(): void
    {
        $out = $this->service->compute(
            $this->makePrediction(['boost_category' => 'first_scorer', 'first_scorer_player_id' => 7]),
            $this->actualResult(['first_scorer_player_id' => 7]),
            $this->defaultRules()
        );

        // first_scorer 10 -> 20, winner stays 3
        $this->assertSame(20, $this->pointsFor($out['breakdown'], 'first_scorer'));
        $this->assertSame(3, $this->pointsFor($out['breakdown'], 'winner'));

        foreach ($out['breakdown'] as $row) {
            $this->assertSame($row['category'] === 'first_scorer', $row['boosted']);
        }
    }

    public function test_disabled_rule_is_skipped(): void
    {
        $rules = $this->defaultRules()->map(function (ScoringRule $r) {
            if ($r->category === ScoringCategory::FirstScorer) {
                $r->enabled = false;
            }
            return $r;
        });

        $out = $this->service->compute(
            $this->makePrediction(['first_scorer_player_id' => 7]),
            $this->actualResult(['first_scorer_player_id' => 7]),
            $rules
        );

        $categories = array_column($out['breakdown'], 'category');
        $this->assertNotContains('first_scorer', $categories);
    }
}
