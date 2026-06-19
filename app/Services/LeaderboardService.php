<?php

namespace App\Services;

use App\Models\Quiniela;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    public function __construct(private ScoringService $scoring) {}

    private function cacheKey(Quiniela $quiniela): string
    {
        return "leaderboard:{$quiniela->id}";
    }

    /**
     * Re-score every prediction for the quiniela and persist the totals and
     * per-category breakdown. Cheap (<= 50 rows) so we just run it on every
     * result change. Busts the leaderboard cache afterwards.
     */
    public function recalculate(Quiniela $quiniela): void
    {
        $result = $quiniela->result()->first();
        $rules = $quiniela->scoringRules()->get();

        if ($result) {
            foreach ($quiniela->predictions()->get() as $prediction) {
                $scored = $this->scoring->compute($prediction, $result, $rules);
                $prediction->forceFill([
                    'total_points' => $scored['total'],
                    'points_breakdown' => $scored['breakdown'],
                ])->save();
            }
        }

        Cache::forget($this->cacheKey($quiniela));
    }

    /**
     * Ordered ranking: most points first, earliest submission wins ties.
     * Cached for 10s so heavy party-time polling stays light.
     *
     * @return array<int,array<string,mixed>>
     */
    public function leaderboard(Quiniela $quiniela): array
    {
        return Cache::remember($this->cacheKey($quiniela), 10, function () use ($quiniela) {
            $rows = $quiniela->predictions()
                ->with('user:id,name')
                ->orderByDesc('total_points')
                ->orderBy('submitted_at')
                ->get();

            return $rows->values()->map(fn ($prediction, $index) => [
                'position' => $index + 1,
                'user_id' => $prediction->user_id,
                'name' => $prediction->user?->name,
                'total_points' => $prediction->total_points,
                'submitted_at' => $prediction->submitted_at,
                'boost_category' => $prediction->boost_category,
            ])->all();
        });
    }
}
