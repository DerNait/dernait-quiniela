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

        // While predictions are still open the match hasn't started, so nobody
        // has points yet. Scoring against the empty 0-0 result would hand out
        // misleading "provisional" points (e.g. "no red card" already true), so
        // we keep everyone at zero until the quiniela closes.
        $open = $quiniela->isOpen();

        foreach ($quiniela->predictions()->get() as $prediction) {
            if ($open || ! $result) {
                $prediction->forceFill(['total_points' => 0, 'points_breakdown' => null])->save();
                continue;
            }

            $scored = $this->scoring->compute($prediction, $result, $rules);
            $prediction->forceFill([
                'total_points' => $scored['total'],
                'points_breakdown' => $scored['breakdown'],
            ])->save();
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
            // Until predictions close everyone is tied at zero, ordered only by
            // who submitted first.
            $open = $quiniela->isOpen();

            $rows = $quiniela->predictions()
                ->with('user:id,name')
                ->when($open, fn ($q) => $q->orderBy('submitted_at'))
                ->when(! $open, fn ($q) => $q->orderByDesc('total_points')->orderBy('submitted_at'))
                ->get();

            return $rows->values()->map(fn ($prediction, $index) => [
                'position' => $index + 1,
                'user_id' => $prediction->user_id,
                'name' => $prediction->user?->name,
                'total_points' => $open ? 0 : $prediction->total_points,
                'submitted_at' => $prediction->submitted_at,
                'boost_category' => $prediction->boost_category,
            ])->all();
        });
    }
}
