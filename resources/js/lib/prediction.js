// Shared mapping between a prediction and a human-readable value per scoring
// category. Used by PredictionDetail (read-only) and the prediction form's
// combined "answer + x2" box, so both stay perfectly in sync.

export const CATEGORY_ORDER = [
    'winner', 'btts', 'ht_winner', 'first_team', 'goal_diff', 'total_goals',
    'ht_exact', 'exact', 'first_scorer', 'red_card', 'penalty', 'first_minute',
];

export const CATEGORY_LABELS = {
    winner: 'Ganador del partido',
    btts: 'Ambos equipos marcan',
    ht_winner: 'Ganador al medio tiempo',
    first_team: 'Equipo del primer gol',
    goal_diff: 'Diferencia de goles',
    total_goals: 'Total de goles exacto',
    ht_exact: 'Marcador exacto al medio tiempo',
    exact: 'Marcador exacto final',
    first_scorer: 'Jugador del primer gol',
    red_card: '¿Habrá tarjeta roja?',
    penalty: '¿Habrá penal?',
    first_minute: 'Minuto del primer gol',
};

/** What the participant predicted for a category, as a display string. */
export function predictionValue(p, key, { homeTeam = 'Local', awayTeam = 'Visitante' } = {}) {
    const outcome = (h, a) => (h > a ? homeTeam : h < a ? awayTeam : 'Empate');
    const teamName = (t) => (t === 'home' ? homeTeam : t === 'away' ? awayTeam : 'Sin goles');
    const signed = (n) => (n > 0 ? `+${n}` : `${n}`);

    switch (key) {
        case 'winner': return outcome(p.exact_home, p.exact_away);
        case 'btts': return p.exact_home > 0 && p.exact_away > 0 ? 'Sí' : 'No';
        case 'ht_winner': return outcome(p.ht_home, p.ht_away);
        case 'first_team': return teamName(p.first_scoring_team);
        case 'goal_diff': return signed(p.exact_home - p.exact_away);
        case 'total_goals': return String(p.exact_home + p.exact_away);
        case 'ht_exact': return `${p.ht_home} - ${p.ht_away}`;
        case 'exact': return `${p.exact_home} - ${p.exact_away}`;
        case 'first_scorer': return p.first_scorer_name || 'Sin goles';
        case 'red_card': return p.red_card ? 'Sí' : 'No';
        case 'penalty': return p.penalty ? 'Sí' : 'No';
        case 'first_minute': return p.first_goal_minute ? `${p.first_goal_minute}'` : '—';
        default: return '';
    }
}
