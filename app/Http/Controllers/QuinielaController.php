<?php

namespace App\Http\Controllers;

use App\Http\Presenters\QuinielaPresenter;
use App\Models\Quiniela;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class QuinielaController extends Controller
{
    public function index()
    {
        $quinielas = Quiniela::orderBy('kickoff_at')->get();

        return response()->json([
            'quinielas' => $quinielas->map(fn ($q) => QuinielaPresenter::summary($q)),
        ]);
    }

    public function show(Request $request, Quiniela $quiniela)
    {
        return response()->json([
            'quiniela' => QuinielaPresenter::detail($quiniela, $request->user()),
        ]);
    }

    public function leaderboard(Quiniela $quiniela, LeaderboardService $leaderboard)
    {
        return response()->json([
            'leaderboard' => $leaderboard->leaderboard($quiniela),
        ]);
    }

    public function live(Quiniela $quiniela)
    {
        return response()->json([
            'live' => QuinielaPresenter::live($quiniela),
        ]);
    }

    /**
     * Everyone's predictions + live points breakdown. Only revealed once the
     * predictions are closed (match locked/live/finished or past kickoff), so
     * nobody can peek before submitting. Used by the expandable leaderboard.
     */
    public function predictions(Quiniela $quiniela)
    {
        if ($quiniela->isOpen()) {
            return response()->json(['closed' => false, 'predictions' => []]);
        }

        $predictions = $quiniela->predictions()
            ->with(['user:id,name', 'firstScorer'])
            ->get()
            ->map(fn ($p) => array_merge(QuinielaPresenter::prediction($p), [
                'user_id' => $p->user_id,
                'name' => $p->user?->name,
            ]))
            ->values()
            ->all();

        return response()->json([
            'closed' => true,
            'home_team' => $quiniela->home_team,
            'away_team' => $quiniela->away_team,
            'predictions' => $predictions,
        ]);
    }
}
