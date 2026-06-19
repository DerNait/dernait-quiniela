<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useIntervalFn } from '@vueuse/core';
import api from '../lib/api';
import { statusLabel, statusClasses } from '../lib/format';

const props = defineProps({
    quinielaId: { type: [Number, String], required: true },
});

const live = ref(null);

async function load() {
    const { data } = await api.get(`/quinielas/${props.quinielaId}/live`);
    live.value = data.live;
}

// Light polling: refresh every 8s while mounted.
const { pause, resume } = useIntervalFn(load, 8000, { immediate: false });

onMounted(async () => {
    await load();
    resume();
});
onUnmounted(pause);

defineExpose({ reload: load });
</script>

<template>
    <div v-if="live" class="space-y-4">
        <div class="rounded-2xl border border-zinc-800 bg-gradient-to-b from-zinc-900 to-zinc-900/40 p-5">
            <div class="mb-4 flex justify-center">
                <span class="rounded-full px-3 py-0.5 text-xs font-bold uppercase tracking-wide ring-1" :class="statusClasses(live.status)">
                    {{ statusLabel(live.status) }}
                </span>
            </div>
            <div class="flex items-center justify-around">
                <div class="flex flex-1 flex-col items-center gap-1">
                    <span class="text-4xl">{{ live.home_flag }}</span>
                    <span class="text-center text-xs font-semibold">{{ live.home_team }}</span>
                </div>
                <div class="px-3 text-center">
                    <div class="text-5xl font-black tabular-nums">{{ live.result.home_score }} - {{ live.result.away_score }}</div>
                    <div class="mt-1 text-xs text-zinc-500">MT {{ live.result.ht_home }} - {{ live.result.ht_away }}</div>
                </div>
                <div class="flex flex-1 flex-col items-center gap-1">
                    <span class="text-4xl">{{ live.away_flag }}</span>
                    <span class="text-center text-xs font-semibold">{{ live.away_team }}</span>
                </div>
            </div>

            <div class="mt-4 flex justify-center gap-2 text-xs">
                <span v-if="live.result.red_card" class="rounded-full bg-rose-500/15 px-2 py-0.5 text-rose-300">
                    <font-awesome-icon icon="fa-solid fa-square" class="mr-0.5" /> Tarjeta roja
                </span>
                <span v-if="live.result.penalty" class="rounded-full bg-amber-500/15 px-2 py-0.5 text-amber-300">
                    <font-awesome-icon icon="fa-solid fa-bullseye" class="mr-0.5" /> Penal
                </span>
            </div>
        </div>

        <!-- Event feed -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-zinc-400">Minuto a minuto</h3>
            <div v-if="!live.events.length" class="py-4 text-center text-sm text-zinc-500">
                Aún no hay jugadas registradas.
            </div>
            <ul v-else class="space-y-2">
                <li
                    v-for="ev in live.events"
                    :key="ev.id"
                    class="flex items-center gap-3 text-sm"
                    :class="ev.team === 'away' ? 'flex-row-reverse text-right' : ''"
                >
                    <span class="w-9 shrink-0 font-black tabular-nums text-zinc-500">{{ ev.minute }}'</span>
                    <font-awesome-icon
                        class="text-lg"
                        :icon="ev.type === 'goal' ? 'fa-solid fa-futbol' : ev.type === 'red_card' ? 'fa-solid fa-square' : 'fa-solid fa-bullseye'"
                        :class="ev.type === 'red_card' ? 'text-rose-500' : ev.type === 'penalty' ? 'text-amber-400' : 'text-zinc-100'"
                    />
                    <span>
                        <span class="font-semibold">{{ ev.player_name || (ev.team === 'home' ? live.home_team : live.away_team) }}</span>
                        <span v-if="ev.type === 'penalty'" class="text-zinc-400"> · penal</span>
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
