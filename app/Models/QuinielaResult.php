<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuinielaResult extends Model
{
    protected $fillable = [
        'quiniela_id', 'home_score', 'away_score', 'ht_home', 'ht_away',
        'first_scoring_team', 'first_scorer_player_id', 'first_goal_minute',
        'red_card', 'penalty',
    ];

    protected $casts = [
        'red_card' => 'boolean',
        'penalty' => 'boolean',
    ];

    public function quiniela(): BelongsTo
    {
        return $this->belongsTo(Quiniela::class);
    }

    public function firstScorer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'first_scorer_player_id');
    }
}
