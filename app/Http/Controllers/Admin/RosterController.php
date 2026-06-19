<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Quiniela;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function store(Request $request, Quiniela $quiniela)
    {
        $data = $request->validate([
            'team' => ['required', 'in:home,away'],
            'name' => ['required', 'string', 'max:60'],
            'number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'kind' => ['nullable', 'in:player,own_goal'],
        ]);

        $player = $quiniela->players()->create([
            ...$data,
            'kind' => $data['kind'] ?? 'player',
        ]);

        return response()->json(['player' => $player], 201);
    }

    public function destroy(Player $player)
    {
        $player->delete();

        return response()->json(['message' => 'Jugador eliminado.']);
    }
}
