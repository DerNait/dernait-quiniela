<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = ref({ email: '', password: '' });
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.login(form.value);
        router.push(route.query.redirect || { name: 'home' });
    } catch (e) {
        error.value = e.response?.data?.message || 'No pudimos iniciar sesión.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="mx-auto mt-6 max-w-sm">
        <div class="mb-8 text-center">
            <font-awesome-icon icon="fa-solid fa-futbol" class="text-5xl text-red-500" />
            <h1 class="mt-2 text-2xl font-black">Quiniela del Mundial</h1>
            <p class="text-sm text-zinc-400">Inicia sesión para jugar</p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm text-zinc-400">Correo</label>
                <input
                    v-model="form.email" type="email" required autocomplete="email"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-base outline-none focus:border-red-500"
                    placeholder="tucorreo@ejemplo.com"
                />
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">Contraseña</label>
                <input
                    v-model="form.password" type="password" required autocomplete="current-password"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-base outline-none focus:border-red-500"
                    placeholder="••••••••"
                />
            </div>

            <p v-if="error" class="rounded-lg bg-rose-500/10 px-3 py-2 text-sm text-rose-300">{{ error }}</p>

            <button
                type="submit" :disabled="loading"
                class="w-full rounded-xl bg-red-500 py-3 text-base font-bold text-white active:scale-95 disabled:opacity-60"
            >{{ loading ? 'Entrando…' : 'Entrar' }}</button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-400">
            ¿No tienes cuenta?
            <RouterLink :to="{ name: 'register' }" class="font-semibold text-red-400">Crear cuenta</RouterLink>
        </p>
    </div>
</template>
