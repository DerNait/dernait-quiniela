<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [
        'user_id', 'quiniela_id',
        'exact_home', 'exact_away', 'ht_home', 'ht_away',
        'first_scoring_team', 'first_scorer_player_id',
        'red_card', 'penalty', 'first_goal_minute',
        'boost_category', 'total_points', 'points_breakdown', 'submitted_at',
    ];

    protected $casts = [
        'red_card' => 'boolean',
        'penalty' => 'boolean',
        'points_breakdown' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiniela(): BelongsTo
    {
        return $this->belongsTo(Quiniela::class);
    }

    public function firstScorer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'first_scorer_player_id');
    }
}
