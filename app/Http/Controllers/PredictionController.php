<?php

namespace App\Http\Controllers;

use App\Http\Presenters\QuinielaPresenter;
use App\Http\Requests\StorePredictionRequest;
use App\Models\Prediction;
use App\Models\Quiniela;
use App\Services\LeaderboardService;

class PredictionController extends Controller
{
    /**
     * Create or update the authenticated user's prediction. Allowed only while
     * the quiniela is open (before kickoff). submitted_at is refreshed on every
     * save so the tie-breaker reflects when they locked in their final answer.
     */
    public function store(StorePredictionRequest $request, Quiniela $quiniela, LeaderboardService $leaderboard)
    {
        if (! $quiniela->isOpen()) {
            return response()->json([
                'message' => 'Las predicciones para este partido ya están cerradas.',
            ], 422);
        }

        $data = $request->validated();
        $data['first_scorer_player_id'] = $data['first_scorer_player_id'] ?? null;
        $data['first_goal_minute'] = $data['first_goal_minute'] ?? null;
        $data['boost_category'] = $data['boost_category'] ?? null;
        $data['submitted_at'] = now();

        $prediction = Prediction::updateOrCreate(
            ['user_id' => $request->user()->id, 'quiniela_id' => $quiniela->id],
            $data,
        );

        // Keep cached totals coherent if a result already exists.
        $leaderboard->recalculate($quiniela);

        return response()->json([
            'my_prediction' => QuinielaPresenter::prediction($prediction->fresh('firstScorer')),
        ]);
    }
}
