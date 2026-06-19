<script setup>
import { computed } from 'vue';

const props = defineProps({
    prediction: { type: Object, required: true },
    homeTeam: { type: String, default: 'Local' },
    awayTeam: { type: String, default: 'Visitante' },
    // Show the points earned per category (only meaningful once the match started).
    showPoints: { type: Boolean, default: true },
    // Optional custom section heading.
    heading: { type: String, default: '' },
});

// Category order + labels mirror the backend enum, used when there is no
// breakdown yet (e.g. before the match) so we can still show every pick.
const ORDER = [
    'winner', 'btts', 'ht_winner', 'first_team', 'goal_diff', 'total_goals',
    'ht_exact', 'exact', 'first_scorer', 'red_card', 'penalty', 'first_minute',
];
const LABELS = {
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

function outcome(h, a) {
    if (h > a) return props.homeTeam;
    if (h < a) return props.awayTeam;
    return 'Empate';
}
function teamName(team) {
    if (team === 'home') return props.homeTeam;
    if (team === 'away') return props.awayTeam;
    return 'Sin goles';
}
function signed(n) {
    return n > 0 ? `+${n}` : `${n}`;
}

// What the participant predicted for a given category, as a display string.
function pickValue(key) {
    const p = props.prediction;
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

const rows = computed(() => {
    const p = props.prediction;
    const breakdown = props.showPoints ? p.points_breakdown : null;

    if (breakdown?.length) {
        return breakdown.map((r) => ({
            category: r.category,
            label: r.label,
            value: pickValue(r.category),
            points: r.points,
            boosted: p.boost_category === r.category,
            showPts: true,
        }));
    }

    return ORDER.map((key) => ({
        category: key,
        label: LABELS[key],
        value: pickValue(key),
        points: 0,
        boosted: p.boost_category === key,
        showPts: false,
    }));
});
</script>

<template>
    <section class="rounded-xl border border-zinc-800 bg-zinc-900/60 p-3">
        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-zinc-400">
            {{ heading || (showPoints ? 'Predicción y puntos' : 'Predicción') }}
        </h3>
        <ul class="space-y-1.5">
            <li
                v-for="row in rows"
                :key="row.category"
                class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm"
                :class="row.showPts ? (row.points > 0 ? 'bg-emerald-500/10' : 'opacity-70') : ''"
            >
                <span class="flex min-w-0 items-center gap-1.5">
                    <font-awesome-icon
                        v-if="row.showPts"
                        :icon="row.points > 0 ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"
                        :class="row.points > 0 ? 'text-emerald-400' : 'text-zinc-600'"
                    />
                    <span class="truncate text-zinc-300">{{ row.label }}</span>
                    <span v-if="row.boosted" class="shrink-0 rounded bg-red-500/30 px-1 text-[10px] font-black text-red-200">x2</span>
                </span>
                <span class="flex shrink-0 items-center gap-3">
                    <span class="font-bold text-zinc-100">{{ row.value }}</span>
                    <span
                        v-if="row.showPts"
                        class="w-8 text-right font-bold tabular-nums"
                        :class="row.points > 0 ? 'text-emerald-300' : 'text-zinc-500'"
                    >+{{ row.points }}</span>
                </span>
            </li>
        </ul>
    </section>
</template>
