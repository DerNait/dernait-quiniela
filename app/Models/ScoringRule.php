<?php

namespace App\Models;

use App\Enums\ScoringCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    protected $fillable = ['quiniela_id', 'category', 'points', 'enabled'];

    protected $casts = [
        'category' => ScoringCategory::class,
        'enabled' => 'boolean',
    ];

    public function quiniela(): BelongsTo
    {
        return $this->belongsTo(Quiniela::class);
    }
}
