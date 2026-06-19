<?php

namespace Tests\Feature;

use App\Models\Quiniela;
use App\Models\QuinielaResult;
use App\Models\ScoringRule;
use App\Models\User;
use App\Enums\ScoringCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuinielaFlowTest extends TestCase
{
    use RefreshDatabase;

    private function quiniela(array $attributes = []): Quiniela
    {
        $quiniela = Quiniela::create(array_merge([
            'name' => 'A vs B', 'home_team' => 'A', 'away_team' => 'B',
            'kickoff_at' => now()->addHour(), 'status' => 'scheduled',
        ], $attributes));

        foreach (ScoringCategory::cases() as $category) {
            ScoringRule::create([
                'quiniela_id' => $quiniela->id,
                'category' => $category->value,
                'points' => $category->defaultPoints(),
                'enabled' => true,
            ]);
        }
        QuinielaResult::create(['quiniela_id' => $quiniela->id]);

        return $quiniela;
    }

    private function validPrediction(array $overrides = []): array
    {
        return array_merge([
            'exact_home' => 2, 'exact_away' => 1,
            'ht_home' => 1, 'ht_away' => 0,
            'first_scoring_team' => 'home',
            'first_scorer_player_id' => null,
            'red_card' => false, 'penalty' => false,
            'first_goal_minute' => 20,
            'boost_category' => 'winner',
        ], $overrides);
    }

    public function test_guests_cannot_list_quinielas(): void
    {
        $this->getJson('/api/quinielas')->assertUnauthorized();
    }

    public function test_user_can_submit_and_update_a_single_prediction(): void
    {
        $user = User::factory()->create();
        $quiniela = $this->quiniela();
        Sanctum::actingAs($user);

        $this->putJson("/api/quinielas/{$quiniela->id}/prediction", $this->validPrediction())
            ->assertOk();

        // Editing again must update in place, not create a second row.
        $this->putJson("/api/quinielas/{$quiniela->id}/prediction", $this->validPrediction(['exact_home' => 3]))
            ->assertOk();

        $this->assertDatabaseCount('predictions', 1);
        $this->assertSame(3, $quiniela->predictions()->first()->exact_home);
    }

    public function test_predictions_are_rejected_after_lock(): void
    {
        $user = User::factory()->create();
        $quiniela = $this->quiniela(['status' => 'live']);
        Sanctum::actingAs($user);

        $this->putJson("/api/quinielas/{$quiniela->id}/prediction", $this->validPrediction())
            ->assertStatus(422);

        $this->assertDatabaseCount('predictions', 0);
    }

    public function test_admin_gate_protects_admin_routes(): void
    {
        $quiniela = $this->quiniela();

        Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
        $this->putJson("/api/admin/quinielas/{$quiniela->id}/status", ['status' => 'live'])
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $this->putJson("/api/admin/quinielas/{$quiniela->id}/status", ['status' => 'live'])
            ->assertOk();
    }

    public function test_leaderboard_breaks_ties_by_earliest_submission(): void
    {
        $quiniela = $this->quiniela(); // open, so predictions are accepted

        $early = User::factory()->create(['name' => 'Early']);
        $late = User::factory()->create(['name' => 'Late']);

        // Identical predictions -> identical points; earlier submission wins.
        foreach ([[$late, now()], [$early, now()->subMinutes(10)]] as [$user, $time]) {
            Sanctum::actingAs($user);
            $this->putJson("/api/quinielas/{$quiniela->id}/prediction", $this->validPrediction())
                ->assertOk();
            $quiniela->predictions()->where('user_id', $user->id)->update(['submitted_at' => $time]);
        }

        // Settle the match, then re-score everyone.
        $quiniela->update(['status' => 'finished']);
        $quiniela->result()->update([
            'home_score' => 2, 'away_score' => 1, 'ht_home' => 1, 'ht_away' => 0,
            'first_scoring_team' => 'home', 'first_goal_minute' => 20,
        ]);
        app(\App\Services\LeaderboardService::class)->recalculate($quiniela);

        Sanctum::actingAs($early);
        $board = $this->getJson("/api/quinielas/{$quiniela->id}/leaderboard")->json('leaderboard');

        $this->assertSame('Early', $board[0]['name']);
        $this->assertSame('Late', $board[1]['name']);
        $this->assertSame($board[0]['total_points'], $board[1]['total_points']);
    }
}
