<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    protected $fillable = ['quiniela_id', 'type', 'team', 'player_id', 'minute', 'half'];

    public function quiniela(): BelongsTo
    {
        return $this->belongsTo(Quiniela::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
