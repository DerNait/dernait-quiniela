<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useIntervalFn } from '@vueuse/core';
import api from '../lib/api';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
    quinielaId: { type: [Number, String], required: true },
    poll: { type: Boolean, default: true },
});

const auth = useAuthStore();
const rows = ref([]);
const loaded = ref(false);

async function load() {
    const { data } = await api.get(`/quinielas/${props.quinielaId}/leaderboard`);
    rows.value = data.leaderboard;
    loaded.value = true;
}

const { pause, resume } = useIntervalFn(load, 9000, { immediate: false });

onMounted(async () => {
    await load();
    if (props.poll) resume();
});
onUnmounted(pause);

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
                class="flex items-center gap-3 rounded-xl border px-3 py-2.5"
                :class="row.user_id === auth.user?.id
                    ? 'border-red-500/50 bg-red-500/10'
                    : 'border-zinc-800 bg-zinc-900/60'"
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
            </li>
        </ul>
    </div>
</template>
