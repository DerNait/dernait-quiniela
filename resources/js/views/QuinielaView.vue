<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../lib/api';
import { kickoffText, countdown, statusLabel, statusClasses } from '../lib/format';
import PredictionForm from '../components/PredictionForm.vue';
import PredictionSummary from '../components/PredictionSummary.vue';
import LiveBoard from '../components/LiveBoard.vue';
import Leaderboard from '../components/Leaderboard.vue';

const route = useRoute();
const quiniela = ref(null);
const loading = ref(true);
const tab = ref('prediction');

async function load() {
    const { data } = await api.get(`/quinielas/${route.params.id}`);
    quiniela.value = data.quiniela;
    // Default to the live tab once predictions are closed.
    tab.value = quiniela.value.is_open ? 'prediction' : 'live';
    loading.value = false;
}

onMounted(load);

const tabs = computed(() => [
    {
        key: 'prediction',
        icon: quiniela.value?.is_open ? 'fa-solid fa-wand-magic-sparkles' : 'fa-solid fa-clipboard-list',
        label: quiniela.value?.is_open ? 'Predecir' : 'Mi quiniela',
    },
    { key: 'live', icon: 'fa-solid fa-tv', label: 'En vivo' },
    { key: 'ranking', icon: 'fa-solid fa-trophy', label: 'Ranking' },
]);

function onSaved(prediction) {
    quiniela.value.my_prediction = prediction;
}
</script>

<template>
    <div v-if="loading" class="space-y-3">
        <div class="h-24 animate-pulse rounded-2xl bg-zinc-900" />
        <div class="h-64 animate-pulse rounded-2xl bg-zinc-900" />
    </div>

    <div v-else>
        <!-- Header -->
        <div class="mb-4">
            <RouterLink :to="{ name: 'home' }" class="text-sm text-zinc-400">
                <font-awesome-icon icon="fa-solid fa-arrow-left" class="mr-1" />Partidos
            </RouterLink>
            <div class="mt-2 flex items-center justify-between">
                <h1 class="text-xl font-black">{{ quiniela.home_team }} vs {{ quiniela.away_team }}</h1>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase ring-1" :class="statusClasses(quiniela.status)">
                    {{ statusLabel(quiniela.status) }}
                </span>
            </div>
            <p class="mt-1 text-xs text-zinc-400">
                <font-awesome-icon icon="fa-solid fa-calendar-day" class="mr-1" />{{ kickoffText(quiniela.kickoff_at) }}
                <span v-if="quiniela.is_open" class="ml-1 font-semibold text-emerald-300">· cierra en {{ countdown(quiniela.kickoff_at) }}</span>
            </p>
        </div>

        <!-- Tabs -->
        <div class="mb-4 grid grid-cols-3 gap-1 rounded-xl bg-zinc-800/60 p-1">
            <button
                v-for="t in tabs" :key="t.key"
                class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-sm font-semibold transition active:scale-95"
                :class="tab === t.key ? 'bg-red-500 text-white' : 'text-zinc-300'"
                @click="tab = t.key"
            ><font-awesome-icon :icon="t.icon" /> {{ t.label }}</button>
        </div>

        <!-- Panels -->
        <div v-show="tab === 'prediction'">
            <PredictionForm v-if="quiniela.is_open" :quiniela="quiniela" @saved="onSaved" />
            <template v-else>
                <p class="mb-3 rounded-lg bg-zinc-800/60 px-3 py-2 text-center text-xs text-zinc-400">
                    Las predicciones están cerradas. Esto es lo que registraste.
                </p>
                <PredictionSummary :quiniela="quiniela" />
            </template>
        </div>

        <LiveBoard v-if="tab === 'live'" :quiniela-id="quiniela.id" />

        <Leaderboard v-if="tab === 'ranking'" :quiniela-id="quiniela.id" />
    </div>
</template>
