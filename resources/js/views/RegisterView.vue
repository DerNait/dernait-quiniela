<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = ref({ name: '', email: '', password: '' });
const errors = ref({});
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    errors.value = {};
    loading.value = true;
    try {
        await auth.register(form.value);
        router.push({ name: 'home' });
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        error.value = e.response?.data?.message || 'No pudimos crear la cuenta.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="mx-auto mt-6 max-w-sm">
        <div class="mb-8 text-center">
            <font-awesome-icon icon="fa-solid fa-cake-candles" class="text-5xl text-red-500" />
            <h1 class="mt-2 text-2xl font-black">Crear cuenta</h1>
            <p class="text-sm text-zinc-400">Únete a la quiniela de la fiesta</p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm text-zinc-400">Nombre (como te verán)</label>
                <input
                    v-model="form.name" type="text" required maxlength="40"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-base outline-none focus:border-red-500"
                    placeholder="Tu nombre o apodo"
                />
                <p v-if="errors.name" class="mt-1 text-xs text-rose-400">{{ errors.name[0] }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">Correo</label>
                <input
                    v-model="form.email" type="email" required autocomplete="email"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-base outline-none focus:border-red-500"
                    placeholder="tucorreo@ejemplo.com"
                />
                <p v-if="errors.email" class="mt-1 text-xs text-rose-400">{{ errors.email[0] }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">Contraseña</label>
                <input
                    v-model="form.password" type="password" required minlength="6" autocomplete="new-password"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-base outline-none focus:border-red-500"
                    placeholder="Mínimo 6 caracteres"
                />
                <p v-if="errors.password" class="mt-1 text-xs text-rose-400">{{ errors.password[0] }}</p>
            </div>

            <p v-if="error && !Object.keys(errors).length" class="rounded-lg bg-rose-500/10 px-3 py-2 text-sm text-rose-300">{{ error }}</p>

            <button
                type="submit" :disabled="loading"
                class="w-full rounded-xl bg-red-500 py-3 text-base font-bold text-white active:scale-95 disabled:opacity-60"
            >{{ loading ? 'Creando…' : 'Crear cuenta' }}</button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-400">
            ¿Ya tienes cuenta?
            <RouterLink :to="{ name: 'login' }" class="font-semibold text-red-400">Inicia sesión</RouterLink>
        </p>
    </div>
</template>
