<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useIntervalFn } from '@vueuse/core';
import api from '../lib/api';
import { useAuthStore } from '../stores/auth';
import PredictionDetail from './PredictionDetail.vue';

const props = defineProps({
    quinielaId: { type: [Number, String], required: true },
    poll: { type: Boolean, default: true },
    // Allow tapping a row to reveal that participant's prediction + breakdown.
    expandable: { type: Boolean, default: true },
});

const auth = useAuthStore();
const rows = ref([]);
const loaded = ref(false);

// Predictions revealed once closed, keyed by user_id.
const closed = ref(false);
const teams = ref({ home: 'Local', away: 'Visitante' });
const predByUser = ref({});
const expanded = ref(null);

async function load() {
    const { data } = await api.get(`/quinielas/${props.quinielaId}/leaderboard`);
    rows.value = data.leaderboard;
    loaded.value = true;

    if (props.expandable) {
        const { data: p } = await api.get(`/quinielas/${props.quinielaId}/predictions`);
        closed.value = p.closed;
        if (p.closed) {
            teams.value = { home: p.home_team, away: p.away_team };
            predByUser.value = Object.fromEntries(p.predictions.map((pr) => [pr.user_id, pr]));
        }
    }
}

const { pause, resume } = useIntervalFn(load, 9000, { immediate: false });

onMounted(async () => {
    await load();
    if (props.poll) resume();
});
onUnmounted(pause);

function toggle(userId) {
    if (!closed.value) return;
    expanded.value = expanded.value === userId ? null : userId;
}

// Gold / silver / bronze colour for the top three, else null.
const medalColor = (pos) => ({ 1: 'text-yellow-400', 2: 'text-zinc-300', 3: 'text-amber-600' }[pos] ?? null);
</script>

<template>
    <div>
        <div v-if="loaded && !rows.length" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 text-center text-zinc-400">
            Todavía nadie ha hecho su predicción.
        </div>

        <ul v-else class="space-y-2">
            <li
                v-for="row in rows"
                :key="row.user_id"
                class="rounded-xl border"
                :class="row.user_id === auth.user?.id
                    ? 'border-red-500/50 bg-red-500/10'
                    : 'border-zinc-800 bg-zinc-900/60'"
            >
                <!-- Row header -->
                <button
                    class="flex w-full items-center gap-3 px-3 py-2.5 text-left"
                    :class="closed && 'active:scale-[0.99]'"
                    @click="toggle(row.user_id)"
                >
                    <span class="w-7 text-center text-lg font-black tabular-nums">
                        <font-awesome-icon v-if="medalColor(row.position)" icon="fa-solid fa-medal" :class="medalColor(row.position)" />
                        <span v-else class="text-zinc-500">{{ row.position }}</span>
                    </span>
                    <span class="flex-1 truncate font-semibold">
                        {{ row.name }}
                        <span v-if="row.user_id === auth.user?.id" class="text-xs font-normal text-red-300">(tú)</span>
                    </span>
                    <span class="text-xl font-black tabular-nums">{{ row.total_points }}</span>
                    <font-awesome-icon
                        v-if="closed && predByUser[row.user_id]"
                        :icon="expanded === row.user_id ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"
                        class="text-xs text-zinc-500"
                    />
                </button>

                <!-- Expanded prediction -->
                <div v-if="expanded === row.user_id && predByUser[row.user_id]" class="border-t border-zinc-800 p-3">
                    <PredictionDetail
                        :prediction="predByUser[row.user_id]"
                        :home-team="teams.home"
                        :away-team="teams.away"
                        :show-points="true"
                    />
                </div>
            </li>
        </ul>

        <p v-if="closed && rows.length" class="mt-3 text-center text-xs text-zinc-500">
            <font-awesome-icon icon="fa-solid fa-hand-pointer" class="mr-1" />Toca a un participante para ver su predicción y dónde puso su x2.
        </p>
    </div>
</template>
