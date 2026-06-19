<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quiniela extends Model
{
    protected $fillable = [
        'name', 'home_team', 'away_team', 'home_flag', 'away_flag',
        'kickoff_at', 'status', 'api_fixture_id',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
    ];

    public function result(): HasOne
    {
        return $this->hasOne(QuinielaResult::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class)->orderBy('minute');
    }

    public function scoringRules(): HasMany
    {
        return $this->hasMany(ScoringRule::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Predictions are open only before kickoff while still in the scheduled
     * state. Once the match is locked/live/finished the form is read-only.
     */
    public function isOpen(): bool
    {
        return $this->status === 'scheduled' && now()->lt($this->kickoff_at);
    }
}
