<script setup>
import { computed } from 'vue';

const props = defineProps({
    prediction: { type: Object, required: true },
    homeTeam: { type: String, default: 'Local' },
    awayTeam: { type: String, default: 'Visitante' },
    // Show the per-category points breakdown (only meaningful once the match started).
    showPoints: { type: Boolean, default: true },
});

const pred = computed(() => props.prediction);
const hasBreakdown = computed(() => props.showPoints && pred.value?.points_breakdown?.length);

function teamName(team) {
    if (team === 'home') return props.homeTeam;
    if (team === 'away') return props.awayTeam;
    return 'Sin goles';
}
</script>

<template>
    <div class="space-y-3">
        <!-- Picks -->
        <section class="space-y-2 rounded-xl border border-zinc-800 bg-zinc-900/60 p-3 text-sm">
            <div class="flex justify-between"><span class="text-zinc-400">Marcador final</span><span class="font-bold">{{ pred.exact_home }} - {{ pred.exact_away }}</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Medio tiempo</span><span class="font-bold">{{ pred.ht_home }} - {{ pred.ht_away }}</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Marca primero</span><span class="font-bold">{{ teamName(pred.first_scoring_team) }}</span></div>
            <div v-if="pred.first_scorer_name" class="flex justify-between"><span class="text-zinc-400">Primer goleador</span><span class="font-bold">{{ pred.first_scorer_name }}</span></div>
            <div v-if="pred.first_goal_minute" class="flex justify-between"><span class="text-zinc-400">Minuto 1er gol</span><span class="font-bold">{{ pred.first_goal_minute }}'</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Tarjeta roja</span><span class="font-bold">{{ pred.red_card ? 'Sí' : 'No' }}</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Penal</span><span class="font-bold">{{ pred.penalty ? 'Sí' : 'No' }}</span></div>
        </section>

        <!-- Breakdown -->
        <section v-if="hasBreakdown" class="rounded-xl border border-zinc-800 bg-zinc-900/60 p-3">
            <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-zinc-400">Desglose de puntos</h3>
            <ul class="space-y-1.5">
                <li
                    v-for="row in pred.points_breakdown"
                    :key="row.category"
                    class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm"
                    :class="row.points > 0 ? 'bg-emerald-500/10' : 'opacity-60'"
                >
                    <span class="flex items-center gap-1.5">
                        <font-awesome-icon
                            :icon="row.points > 0 ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"
                            :class="row.points > 0 ? 'text-emerald-400' : 'text-zinc-600'"
                        />
                        <span>{{ row.label }}</span>
                        <span v-if="row.boosted" class="rounded bg-red-500/30 px-1 text-[10px] font-black text-red-200">x2</span>
                    </span>
                    <span class="font-bold tabular-nums" :class="row.points > 0 ? 'text-emerald-300' : 'text-zinc-500'">
                        +{{ row.points }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
