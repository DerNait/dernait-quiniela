<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin, optional client for API-Football (api-sports.io). Only used when an API
 * key is configured; the app is fully usable without it. We deliberately fetch
 * on demand (an admin button) rather than polling, to respect the 100 req/day
 * free tier.
 */
class ApiFootballService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.apifootball.key'));
    }

    /**
     * Fetch a fixture and normalise the bits we can map automatically. The first
     * goalscorer is intentionally left to the admin, since API player names
     * rarely match the local roster used for the prediction dropdown.
     *
     * @return array<string,mixed>|null
     */
    public function fetchFixture(int $fixtureId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::withHeaders([
            'x-apisports-key' => config('services.apifootball.key'),
        ])->get(rtrim(config('services.apifootball.base'), '/').'/fixtures', [
            'id' => $fixtureId,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $fixture = data_get($response->json(), 'response.0');
        if (! $fixture) {
            return null;
        }

        $events = collect(data_get($fixture, 'events', []))
            ->filter(fn ($e) => data_get($e, 'type') === 'Goal')
            ->map(fn ($e) => [
                'minute' => (int) data_get($e, 'time.elapsed'),
                'team_id' => data_get($e, 'team.id'),
                'player' => data_get($e, 'player.name'),
            ])
            ->sortBy('minute')
            ->values();

        $homeId = data_get($fixture, 'teams.home.id');
        $first = $events->first();

        return [
            'status' => data_get($fixture, 'fixture.status.short'),
            'home_score' => (int) data_get($fixture, 'goals.home'),
            'away_score' => (int) data_get($fixture, 'goals.away'),
            'ht_home' => (int) data_get($fixture, 'score.halftime.home'),
            'ht_away' => (int) data_get($fixture, 'score.halftime.away'),
            'first_scoring_team' => $first
                ? ($first['team_id'] === $homeId ? 'home' : 'away')
                : 'none',
            'first_goal_minute' => $first['minute'] ?? null,
            'first_scorer_name' => $first['player'] ?? null, // admin maps to roster
        ];
    }
}
