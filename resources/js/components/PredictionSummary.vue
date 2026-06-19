<script setup>
import { computed } from 'vue';
import PredictionDetail from './PredictionDetail.vue';

const props = defineProps({
    quiniela: { type: Object, required: true },
});

const pred = computed(() => props.quiniela.my_prediction);
const showPoints = computed(() => ['live', 'finished'].includes(props.quiniela.status) && pred.value?.points_breakdown);
</script>

<template>
    <div v-if="!pred" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 text-center text-zinc-400">
        No registraste una predicción para este partido.
    </div>

    <div v-else class="space-y-4">
        <div v-if="showPoints" class="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-center">
            <p class="text-xs uppercase tracking-wide text-red-300">Tus puntos</p>
            <p class="text-4xl font-black">{{ pred.total_points }}</p>
        </div>

        <h3 class="text-sm font-bold uppercase tracking-wide text-zinc-400">Tu predicción</h3>
        <PredictionDetail
            :prediction="pred"
            :home-team="quiniela.home_team"
            :away-team="quiniela.away_team"
            :show-points="showPoints"
        />
    </div>
</template>
