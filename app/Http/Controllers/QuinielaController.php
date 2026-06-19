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
}
