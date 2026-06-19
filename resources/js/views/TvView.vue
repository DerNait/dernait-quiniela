<script setup>
import { useRoute } from 'vue-router';
import LiveBoard from '../components/LiveBoard.vue';
import Leaderboard from '../components/Leaderboard.vue';

const route = useRoute();
</script>

<template>
    <div class="min-h-screen bg-zinc-950 p-6">
        <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-2">
            <div>
                <h2 class="mb-3 text-center text-2xl font-black tracking-tight"><font-awesome-icon icon="fa-solid fa-tv" class="text-red-500" /> En vivo</h2>
                <LiveBoard :quiniela-id="route.params.id" />
            </div>
            <div>
                <h2 class="mb-3 text-center text-2xl font-black tracking-tight"><font-awesome-icon icon="fa-solid fa-trophy" class="text-yellow-400" /> Ranking</h2>
                <Leaderboard :quiniela-id="route.params.id" />
            </div>
        </div>
        <RouterLink :to="{ name: 'admin' }" class="fixed bottom-4 right-4 text-xs text-zinc-600">salir</RouterLink>
    </div>
</template>
