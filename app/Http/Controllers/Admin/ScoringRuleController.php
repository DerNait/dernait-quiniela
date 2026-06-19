<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScoringCategory;
use App\Http\Controllers\Controller;
use App\Http\Presenters\QuinielaPresenter;
use App\Models\Quiniela;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class ScoringRuleController extends Controller
{
    /** Bulk update the points table, then re-score everyone. */
    public function update(Request $request, Quiniela $quiniela, LeaderboardService $leaderboard)
    {
        $data = $request->validate([
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.category' => ['required', 'in:'.implode(',', ScoringCategory::values())],
            'rules.*.points' => ['required', 'integer', 'min:0', 'max:100'],
            'rules.*.enabled' => ['required', 'boolean'],
        ]);

        foreach ($data['rules'] as $rule) {
            $quiniela->scoringRules()
                ->where('category', $rule['category'])
                ->update(['points' => $rule['points'], 'enabled' => $rule['enabled']]);
        }

        $leaderboard->recalculate($quiniela);

        return response()->json([
            'quiniela' => QuinielaPresenter::detail($quiniela->fresh(), $request->user()),
        ]);
    }
}
