<?php

namespace App\Http\Requests;

use App\Enums\ScoringCategory;
use App\Models\Quiniela;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePredictionRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Quiniela $quiniela */
        $quiniela = $this->route('quiniela');
        $playerIds = $quiniela->players()->pluck('id')->all();

        return [
            'exact_home' => ['required', 'integer', 'min:0', 'max:30'],
            'exact_away' => ['required', 'integer', 'min:0', 'max:30'],
            'ht_home' => ['required', 'integer', 'min:0', 'max:30'],
            'ht_away' => ['required', 'integer', 'min:0', 'max:30'],
            'first_scoring_team' => ['required', 'in:home,away,none'],
            'first_scorer_player_id' => ['nullable', 'integer', 'in:'.implode(',', $playerIds ?: [0])],
            'red_card' => ['required', 'boolean'],
            'penalty' => ['required', 'boolean'],
            'first_goal_minute' => ['nullable', 'integer', 'min:1', 'max:130'],
            'boost_category' => ['nullable', 'in:'.implode(',', ScoringCategory::values())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->validated();
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $totalGoals = ($data['exact_home'] ?? 0) + ($data['exact_away'] ?? 0);

            // Half-time score can never exceed the full-time score.
            if (($data['ht_home'] ?? 0) > ($data['exact_home'] ?? 0)) {
                $validator->errors()->add('ht_home', 'El marcador al medio tiempo no puede superar el final.');
            }
            if (($data['ht_away'] ?? 0) > ($data['exact_away'] ?? 0)) {
                $validator->errors()->add('ht_away', 'El marcador al medio tiempo no puede superar el final.');
            }

            // Coherence between "no goals" and the first goal fields.
            if ($totalGoals === 0 && $data['first_scoring_team'] !== 'none') {
                $validator->errors()->add('first_scoring_team', 'Si predices 0-0, el primer gol debe ser "Sin goles".');
            }
            if ($totalGoals > 0 && $data['first_scoring_team'] === 'none') {
                $validator->errors()->add('first_scoring_team', 'Si predices goles, elige qué equipo marca primero.');
            }
            if ($totalGoals > 0 && empty($data['first_goal_minute'])) {
                $validator->errors()->add('first_goal_minute', 'Indica el minuto aproximado del primer gol.');
            }

            // The x2 wildcard must target an enabled category.
            if (! empty($data['boost_category'])) {
                $enabled = $this->route('quiniela')->scoringRules()
                    ->where('enabled', true)->pluck('category')
                    ->map(fn ($c) => $c instanceof \BackedEnum ? $c->value : $c)
                    ->all();
                if (! in_array($data['boost_category'], $enabled, true)) {
                    $validator->errors()->add('boost_category', 'Esa categoría no está disponible para el x2.');
                }
            }
        });
    }
}
