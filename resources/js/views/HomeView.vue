<script setup>
import { ref, onMounted } from 'vue';
import api from '../lib/api';
import { statusLabel, statusClasses, countdown, kickoffText } from '../lib/format';

const quinielas = ref([]);
const loading = ref(true);

onMounted(async () => {
    const { data } = await api.get('/quinielas');
    quinielas.value = data.quinielas;
    loading.value = false;
});
</script>

<template>
    <div>
        <h1 class="mb-1 text-2xl font-black">Partidos</h1>
        <p class="mb-5 text-sm text-zinc-400">
            Elige un partido y arma tu predicción
            <font-awesome-icon icon="fa-solid fa-wand-magic-sparkles" class="text-red-400" />
        </p>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 2" :key="n" class="h-32 animate-pulse rounded-2xl bg-zinc-900" />
        </div>

        <div v-else class="space-y-4">
            <RouterLink
                v-for="q in quinielas"
                :key="q.id"
                :to="{ name: 'quiniela', params: { id: q.id } }"
                class="block rounded-2xl border border-zinc-800 bg-gradient-to-b from-zinc-900 to-zinc-900/40 p-4 active:scale-[0.99]"
            >
                <div class="mb-3 flex items-center justify-between">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide ring-1"
                        :class="statusClasses(q.status)"
                    >{{ statusLabel(q.status) }}</span>
                    <span class="text-xs text-zinc-400">{{ q.predictions_count }} jugando</span>
                </div>

                <div class="flex items-center justify-around text-center">
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-4xl">{{ q.home_flag }}</span>
                        <span class="text-sm font-semibold">{{ q.home_team }}</span>
                    </div>
                    <span class="px-2 text-lg font-black text-zinc-500">VS</span>
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-4xl">{{ q.away_flag }}</span>
                        <span class="text-sm font-semibold">{{ q.away_team }}</span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between border-t border-zinc-800 pt-3 text-xs text-zinc-400">
                    <span><font-awesome-icon icon="fa-solid fa-calendar-day" class="mr-1" />{{ kickoffText(q.kickoff_at) }}</span>
                    <span v-if="q.is_open" class="font-semibold text-emerald-300">Cierra en {{ countdown(q.kickoff_at) }}</span>
                    <span v-else class="font-semibold text-zinc-500">Predicciones cerradas</span>
                </div>
            </RouterLink>
        </div>
    </div>
</template>
