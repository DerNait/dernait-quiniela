<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = ['quiniela_id', 'team', 'name', 'number', 'kind'];

    public function quiniela(): BelongsTo
    {
        return $this->belongsTo(Quiniela::class);
    }
}
