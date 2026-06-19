<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Presenters\QuinielaPresenter;
use App\Models\MatchEvent;
use App\Models\Quiniela;
use App\Services\ResultService;
use Illuminate\Http\Request;

class MatchEventController extends Controller
{
    /**
     * Record a live event (goal / red card / penalty). The quiniela result is
     * rebuilt from the full event timeline and every prediction re-scored.
     */
    public function store(Request $request, Quiniela $quiniela, ResultService $results)
    {
        $playerIds = $quiniela->players()->pluck('id')->all();

        $data = $request->validate([
            'type' => ['required', 'in:goal,red_card,penalty'],
            'team' => ['required', 'in:home,away'],
            'player_id' => ['nullable', 'integer', 'in:'.implode(',', $playerIds ?: [0])],
            'minute' => ['required', 'integer', 'min:1', 'max:130'],
            'half' => ['required', 'integer', 'in:1,2'],
        ]);

        $quiniela->events()->create($data);
        $results->rebuildFromEvents($quiniela);

        return response()->json(['live' => QuinielaPresenter::live($quiniela->fresh())], 201);
    }

    public function destroy(MatchEvent $event, ResultService $results)
    {
        $quiniela = $event->quiniela;
        $event->delete();
        $results->rebuildFromEvents($quiniela);

        return response()->json(['live' => QuinielaPresenter::live($quiniela->fresh())]);
    }
}
