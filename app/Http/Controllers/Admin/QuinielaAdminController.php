<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScoringCategory;
use App\Http\Controllers\Controller;
use App\Http\Presenters\QuinielaPresenter;
use App\Models\Quiniela;
use App\Models\QuinielaResult;
use App\Models\ScoringRule;
use App\Services\ApiFootballService;
use App\Services\LeaderboardService;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuinielaAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateQuiniela($request);

        $quiniela = DB::transaction(function () use ($data) {
            $quiniela = Quiniela::create($data);

            // Seed the editable points table with sensible defaults.
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
        });

        return response()->json([
            'quiniela' => QuinielaPresenter::detail($quiniela, $request->user()),
        ], 201);
    }

    public function update(Request $request, Quiniela $quiniela)
    {
        $quiniela->update($this->validateQuiniela($request, $quiniela));

        return response()->json([
            'quiniela' => QuinielaPresenter::detail($quiniela, $request->user()),
        ]);
    }

    public function destroy(Quiniela $quiniela)
    {
        $quiniela->delete();

        return response()->json(['message' => 'Quiniela eliminada.']);
    }

    public function updateStatus(Request $request, Quiniela $quiniela)
    {
        $data = $request->validate([
            'status' => ['required', 'in:scheduled,locked,live,finished'],
        ]);

        $quiniela->update($data);

        return response()->json(['quiniela' => QuinielaPresenter::summary($quiniela)]);
    }

    /** Manual override of the result (corrections / finalising without events). */
    public function updateResult(Request $request, Quiniela $quiniela, LeaderboardService $leaderboard)
    {
        $playerIds = $quiniela->players()->pluck('id')->all();

        $data = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:30'],
            'away_score' => ['required', 'integer', 'min:0', 'max:30'],
            'ht_home' => ['required', 'integer', 'min:0', 'max:30'],
            'ht_away' => ['required', 'integer', 'min:0', 'max:30'],
            'first_scoring_team' => ['required', 'in:home,away,none'],
            'first_scorer_player_id' => ['nullable', 'integer', 'in:'.implode(',', $playerIds ?: [0])],
            'first_goal_minute' => ['nullable', 'integer', 'min:1', 'max:130'],
            'red_card' => ['required', 'boolean'],
            'penalty' => ['required', 'boolean'],
        ]);

        $result = QuinielaResult::firstOrNew(['quiniela_id' => $quiniela->id]);
        $result->fill($data)->save();

        $leaderboard->recalculate($quiniela);

        return response()->json(['live' => QuinielaPresenter::live($quiniela->fresh())]);
    }

    /**
     * Reset the match back to its pre-kickoff state for testing/simulation:
     * wipes all live events, clears the result, and reopens predictions.
     * Predictions themselves are kept so you can re-simulate scores against them.
     */
    public function reset(Quiniela $quiniela, LeaderboardService $leaderboard)
    {
        DB::transaction(function () use ($quiniela) {
            $quiniela->events()->delete();

            $result = QuinielaResult::firstOrNew(['quiniela_id' => $quiniela->id]);
            $result->fill([
                'home_score' => 0,
                'away_score' => 0,
                'ht_home' => 0,
                'ht_away' => 0,
                'first_scoring_team' => 'none',
                'first_scorer_player_id' => null,
                'first_goal_minute' => null,
                'red_card' => false,
                'penalty' => false,
            ])->save();

            $quiniela->update(['status' => 'scheduled']);
        });

        $leaderboard->recalculate($quiniela);

        return response()->json([
            'live' => QuinielaPresenter::live($quiniela->fresh()),
            'message' => 'Partido reiniciado.',
        ]);
    }

    /**
     * Generate a random but realistic match (goals with minute/half/scorer from
     * the roster, plus a chance of a red card and a penalty) in one tap, for
     * testing how the scoring and ranking behave. Predictions are kept.
     */
    public function simulate(Quiniela $quiniela, ResultService $results)
    {
        $rosters = $quiniela->players()
            ->where('kind', 'player')
            ->get()
            ->groupBy('team');

        DB::transaction(function () use ($quiniela, $rosters) {
            $quiniela->events()->delete();

            foreach (['home', 'away'] as $team) {
                $goals = random_int(0, 3);
                $squad = $rosters->get($team);

                for ($i = 0; $i < $goals; $i++) {
                    $minute = random_int(1, 90);
                    $quiniela->events()->create([
                        'type' => 'goal',
                        'team' => $team,
                        'player_id' => $squad && $squad->isNotEmpty() ? $squad->random()->id : null,
                        'minute' => $minute,
                        'half' => $minute <= 45 ? 1 : 2,
                    ]);
                }
            }

            // ~35% chance of a red card, ~30% chance of a penalty.
            if (random_int(1, 100) <= 35) {
                $team = ['home', 'away'][random_int(0, 1)];
                $quiniela->events()->create([
                    'type' => 'red_card', 'team' => $team,
                    'player_id' => null, 'minute' => random_int(20, 90),
                    'half' => 2,
                ]);
            }
            if (random_int(1, 100) <= 30) {
                $team = ['home', 'away'][random_int(0, 1)];
                $quiniela->events()->create([
                    'type' => 'penalty', 'team' => $team,
                    'player_id' => null, 'minute' => random_int(10, 90),
                    'half' => 2,
                ]);
            }

            $quiniela->update(['status' => 'finished']);
        });

        // Rebuilds the result from the events we just created and recalculates.
        $results->rebuildFromEvents($quiniela->fresh());

        return response()->json([
            'live' => QuinielaPresenter::live($quiniela->fresh()),
            'message' => 'Partido simulado.',
        ]);
    }

    /** Pull the live score from API-Football (optional helper). */
    public function sync(Quiniela $quiniela, ApiFootballService $api, LeaderboardService $leaderboard)
    {
        if (! $api->isConfigured()) {
            return response()->json(['message' => 'No hay API key configurada.'], 422);
        }
        if (! $quiniela->api_fixture_id) {
            return response()->json(['message' => 'Esta quiniela no tiene un fixture de API asociado.'], 422);
        }

        $data = $api->fetchFixture($quiniela->api_fixture_id);
        if (! $data) {
            return response()->json(['message' => 'No se pudo obtener el fixture.'], 422);
        }

        $result = QuinielaResult::firstOrNew(['quiniela_id' => $quiniela->id]);
        $result->fill([
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
            'ht_home' => $data['ht_home'],
            'ht_away' => $data['ht_away'],
            'first_scoring_team' => $data['first_scoring_team'],
            'first_goal_minute' => $data['first_goal_minute'],
        ])->save();

        $leaderboard->recalculate($quiniela);

        return response()->json([
            'live' => QuinielaPresenter::live($quiniela->fresh()),
            // The admin still maps the scorer to the local roster manually.
            'suggested_scorer_name' => $data['first_scorer_name'],
        ]);
    }

    private function validateQuiniela(Request $request, ?Quiniela $quiniela = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'home_team' => ['required', 'string', 'max:40'],
            'away_team' => ['required', 'string', 'max:40'],
            'home_flag' => ['nullable', 'string', 'max:16'],
            'away_flag' => ['nullable', 'string', 'max:16'],
            'kickoff_at' => ['required', 'date'],
            'api_fixture_id' => ['nullable', 'integer'],
        ]);
    }
}
