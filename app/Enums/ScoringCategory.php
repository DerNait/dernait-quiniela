<?php

namespace App\Enums;

/**
 * Every way a participant can earn points in a quiniela.
 *
 * Each case maps to a row in `scoring_rules` (points + enabled flag) and to a
 * branch in App\Services\ScoringService. The default points encode the
 * "harder to guess = more points" philosophy: an exact first scorer is worth
 * far more than picking the winner.
 */
enum ScoringCategory: string
{
    case Winner = 'winner';          // 1X2 final result
    case Btts = 'btts';              // both teams to score (yes/no)
    case HtWinner = 'ht_winner';     // 1X2 at half time
    case FirstTeam = 'first_team';   // which team scores first
    case GoalDiff = 'goal_diff';     // signed goal difference
    case TotalGoals = 'total_goals'; // exact total number of goals
    case HtExact = 'ht_exact';       // exact half time score
    case Exact = 'exact';            // exact final score
    case FirstScorer = 'first_scorer'; // first goalscorer (hardest)
    case RedCard = 'red_card';       // will there be a red card (yes/no)
    case Penalty = 'penalty';        // will there be a penalty (yes/no)
    case FirstMinute = 'first_minute'; // closeness to first goal minute

    /** Human friendly Spanish label shown in the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Winner => 'Ganador del partido',
            self::Btts => 'Ambos equipos marcan',
            self::HtWinner => 'Ganador al medio tiempo',
            self::FirstTeam => 'Equipo del primer gol',
            self::GoalDiff => 'Diferencia de goles',
            self::TotalGoals => 'Total de goles exacto',
            self::HtExact => 'Marcador exacto al medio tiempo',
            self::Exact => 'Marcador exacto final',
            self::FirstScorer => 'Jugador del primer gol',
            self::RedCard => '¿Habrá tarjeta roja?',
            self::Penalty => '¿Habrá penal?',
            self::FirstMinute => 'Minuto del primer gol',
        };
    }

    /** Default points awarded for a correct prediction in this category. */
    public function defaultPoints(): int
    {
        return match ($this) {
            self::Winner => 3,
            self::Btts => 2,
            self::HtWinner => 2,
            self::FirstTeam => 3,
            self::GoalDiff => 3,
            self::TotalGoals => 4,
            self::HtExact => 5,
            self::Exact => 6,
            self::FirstScorer => 10,
            self::RedCard => 2,
            self::Penalty => 2,
            self::FirstMinute => 5,
        };
    }

    /** Short hint describing how points are earned. */
    public function hint(): string
    {
        return match ($this) {
            self::FirstMinute => 'Exacto da el total; ±2 min y ±5 min dan puntaje parcial.',
            self::FirstScorer => 'La predicción más difícil y la que más puntos otorga.',
            default => 'Puntos completos si aciertas.',
        };
    }

    /** All cases as a plain array of values, handy for validation rules. */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
