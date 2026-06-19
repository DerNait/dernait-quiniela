<script setup>
import { ref, onMounted } from 'vue';
import api from '../lib/api';
import { statusLabel, statusClasses, kickoffText } from '../lib/format';

const quinielas = ref([]);
const showCreate = ref(false);
const creating = ref(false);
const error = ref('');
const form = ref({
    name: '', home_team: '', away_team: '',
    home_flag: '', away_flag: '', kickoff_at: '', api_fixture_id: '',
});

async function load() {
    const { data } = await api.get('/quinielas');
    quinielas.value = data.quinielas;
}
onMounted(load);

async function create() {
    creating.value = true;
    error.value = '';
    try {
        await api.post('/admin/quinielas', {
            ...form.value,
            api_fixture_id: form.value.api_fixture_id || null,
        });
        showCreate.value = false;
        form.value = { name: '', home_team: '', away_team: '', home_flag: '', away_flag: '', kickoff_at: '', api_fixture_id: '' };
        await load();
    } catch (e) {
        error.value = e.response?.data?.message || 'No se pudo crear.';
    } finally {
        creating.value = false;
    }
}
</script>

<template>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-black">Panel admin</h1>
            <button
                class="rounded-full bg-red-500 px-4 py-2 text-sm font-bold text-white active:scale-95"
                @click="showCreate = !showCreate"
            >{{ showCreate ? 'Cancelar' : '+ Nueva' }}</button>
        </div>

        <form v-if="showCreate" class="mb-5 space-y-3 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4" @submit.prevent="create">
            <input v-model="form.name" required placeholder="Nombre (ej. Alemania vs Costa de Marfil)" class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
            <div class="grid grid-cols-2 gap-2">
                <input v-model="form.home_team" required placeholder="Equipo local" class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
                <input v-model="form.away_team" required placeholder="Equipo visitante" class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
                <input v-model="form.home_flag" placeholder="🏳️ local" class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
                <input v-model="form.away_flag" placeholder="🏳️ visitante" class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
            </div>
            <label class="block text-xs text-zinc-400">Fecha y hora de inicio (cierre de predicciones)</label>
            <input v-model="form.kickoff_at" type="datetime-local" required class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
            <input v-model="form.api_fixture_id" placeholder="API-Football fixture id (opcional)" class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
            <p v-if="error" class="text-sm text-rose-400">{{ error }}</p>
            <button type="submit" :disabled="creating" class="w-full rounded-lg bg-emerald-500 py-2.5 font-bold text-white active:scale-95">
                {{ creating ? 'Creando…' : 'Crear quiniela' }}
            </button>
        </form>

        <div class="space-y-3">
            <div v-for="q in quinielas" :key="q.id" class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold">{{ q.home_flag }} {{ q.home_team }} vs {{ q.away_team }} {{ q.away_flag }}</p>
                        <p class="text-xs text-zinc-400">{{ kickoffText(q.kickoff_at) }} · {{ q.predictions_count }} jugando</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-bold uppercase ring-1" :class="statusClasses(q.status)">{{ statusLabel(q.status) }}</span>
                </div>
                <div class="mt-3 flex gap-2">
                    <RouterLink :to="{ name: 'admin.quiniela', params: { id: q.id } }" class="flex-1 rounded-lg bg-red-500 py-2 text-center text-sm font-bold text-white active:scale-95">Gestionar</RouterLink>
                    <RouterLink :to="{ name: 'tv', params: { id: q.id } }" class="rounded-lg bg-zinc-800 px-4 py-2 text-center text-sm font-bold text-zinc-200 active:scale-95"><font-awesome-icon icon="fa-solid fa-tv" /> TV</RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
