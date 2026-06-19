<script setup>
import { computed } from 'vue';
import { CATEGORY_ORDER, CATEGORY_LABELS, predictionValue } from '../lib/prediction';

const props = defineProps({
    prediction: { type: Object, required: true },
    homeTeam: { type: String, default: 'Local' },
    awayTeam: { type: String, default: 'Visitante' },
    // Show the points earned per category (only meaningful once the match started).
    showPoints: { type: Boolean, default: true },
    // Optional custom section heading.
    heading: { type: String, default: '' },
});

const teams = computed(() => ({ homeTeam: props.homeTeam, awayTeam: props.awayTeam }));
const pickValue = (key) => predictionValue(props.prediction, key, teams.value);

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

    return CATEGORY_ORDER.map((key) => ({
        category: key,
        label: CATEGORY_LABELS[key],
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
