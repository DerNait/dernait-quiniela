<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const showChrome = computed(() => auth.isAuthenticated && route.name !== 'tv');

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-full flex flex-col">
        <header
            v-if="showChrome"
            class="sticky top-0 z-20 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur"
        >
            <div class="mx-auto flex max-w-2xl items-center justify-between px-4 py-3">
                <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2 font-bold">
                    <font-awesome-icon icon="fa-solid fa-futbol" class="text-xl text-red-500" />
                    <span class="tracking-tight">Quiniela</span>
                </RouterLink>
                <div class="flex items-center gap-3 text-sm">
                    <RouterLink
                        v-if="auth.isAdmin"
                        :to="{ name: 'admin' }"
                        class="flex items-center gap-1.5 rounded-full bg-red-500/15 px-3 py-1 font-medium text-red-300 ring-1 ring-red-500/30"
                    >
                        <font-awesome-icon icon="fa-solid fa-user-shield" /> Admin
                    </RouterLink>
                    <span class="hidden text-zinc-400 sm:inline">{{ auth.user?.name }}</span>
                    <button
                        class="flex items-center gap-1.5 rounded-full bg-zinc-800 px-3 py-1 text-zinc-300 active:scale-95"
                        @click="logout"
                    >
                        <font-awesome-icon icon="fa-solid fa-right-from-bracket" /> Salir
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-5">
            <RouterView />
        </main>
    </div>
</template>
