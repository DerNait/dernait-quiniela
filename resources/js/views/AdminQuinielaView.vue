<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../lib/api';
import SegmentedControl from '../components/SegmentedControl.vue';
import LiveBoard from '../components/LiveBoard.vue';

const route = useRoute();
const q = ref(null);
const live = ref(null);
const board = ref(null);
const toast = ref('');

const statusOptions = [
    { value: 'scheduled', label: 'Abierta' },
    { value: 'locked', label: 'Cerrada' },
    { value: 'live', label: 'En vivo' },
    { value: 'finished', label: 'Final' },
];

const eventForm = reactive({ type: 'goal', team: 'home', player_id: null, minute: 1, half: 1 });
const ruleDraft = ref([]);

async function loadAll() {
    const [detail, liveRes] = await Promise.all([
        api.get(`/quinielas/${route.params.id}`),
        api.get(`/quinielas/${route.params.id}/live`),
    ]);
    q.value = detail.data.quiniela;
    live.value = liveRes.data.live;
    ruleDraft.value = q.value.rules.map((r) => ({ ...r }));
}
onMounted(loadAll);

function flash(msg) {
    toast.value = msg;
    setTimeout(() => (toast.value = ''), 2000);
}

const rosterForTeam = computed(() => q.value?.roster[eventForm.team] ?? []);

async function setStatus(status) {
    await api.put(`/admin/quinielas/${route.params.id}/status`, { status });
    q.value.status = status;
    flash('Estado actualizado');
}

async function addEvent() {
    const payload = { ...eventForm };
    if (payload.type !== 'goal') payload.player_id = null;
    const { data } = await api.post(`/admin/quinielas/${route.params.id}/events`, payload);
    live.value = data.live;
    board.value?.reload();
    flash('Jugada registrada');
}

async function removeEvent(id) {
    const { data } = await api.delete(`/admin/events/${id}`);
    live.value = data.live;
    flash('Jugada eliminada');
}

async function resetMatch() {
    if (!confirm('¿Reiniciar el partido? Se borrarán todos los goles/jugadas y el marcador vuelve a 0. Las predicciones de los participantes se mantienen.')) return;
    const { data } = await api.post(`/admin/quinielas/${route.params.id}/reset`);
    live.value = data.live;
    q.value.status = 'scheduled';
    board.value?.reload();
    flash('Partido reiniciado');
}

async function simulateMatch() {
    if (!confirm('¿Simular un partido de ejemplo? Se generará un resultado aleatorio (goles, goleadores, quizá roja/penal) y se cerrarán las predicciones. Las predicciones se mantienen.')) return;
    const { data } = await api.post(`/admin/quinielas/${route.params.id}/simulate`);
    live.value = data.live;
    q.value.status = 'finished';
    board.value?.reload();
    flash('Partido simulado');
}

async function saveRules() {
    await api.put(`/admin/quinielas/${route.params.id}/rules`, {
        rules: ruleDraft.value.map((r) => ({ category: r.category, points: Number(r.points), enabled: r.enabled })),
    });
    flash('Puntajes guardados');
}

const newPlayer = reactive({ team: 'home', name: '', number: '' });
async function addPlayer() {
    const { data } = await api.post(`/admin/quinielas/${route.params.id}/players`, {
        team: newPlayer.team, name: newPlayer.name, number: newPlayer.number || null,
    });
    q.value.roster[newPlayer.team].push({ id: data.player.id, name: data.player.name, number: data.player.number, kind: data.player.kind });
    newPlayer.name = '';
    newPlayer.number = '';
}
async function removePlayer(team, id) {
    await api.delete(`/admin/players/${id}`);
    q.value.roster[team] = q.value.roster[team].filter((p) => p.id !== id);
}

async function syncApi() {
    try {
        const { data } = await api.post(`/admin/quinielas/${route.params.id}/sync`);
        live.value = data.live;
        board.value?.reload();
        flash(data.suggested_scorer_name ? `Sincronizado. Goleador sugerido: ${data.suggested_scorer_name}` : 'Sincronizado');
    } catch (e) {
        flash(e.response?.data?.message || 'No se pudo sincronizar');
    }
}
</script>

<template>
    <div v-if="q" class="space-y-5 pb-10">
        <div>
            <RouterLink :to="{ name: 'admin' }" class="text-sm text-zinc-400"><font-awesome-icon icon="fa-solid fa-arrow-left" class="mr-1" />Panel</RouterLink>
            <h1 class="mt-1 text-xl font-black">{{ q.home_team }} vs {{ q.away_team }}</h1>
        </div>

        <!-- Status -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-zinc-400">Estado del partido</h2>
            <SegmentedControl :model-value="q.status" :options="statusOptions" @update:model-value="setStatus" />
            <p class="mt-2 text-xs text-zinc-500">Al pasar a "En vivo" o "Final" se cierran las predicciones.</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button
                    class="rounded-lg border border-red-500/40 bg-red-500/10 py-2 text-sm font-bold text-red-300 active:scale-95"
                    @click="simulateMatch"
                >
                    <font-awesome-icon icon="fa-solid fa-bolt" class="mr-1" /> Simular partido
                </button>
                <button
                    class="rounded-lg border border-rose-500/40 bg-rose-500/10 py-2 text-sm font-bold text-rose-300 active:scale-95"
                    @click="resetMatch"
                >
                    <font-awesome-icon icon="fa-solid fa-rotate" class="mr-1" /> Reiniciar
                </button>
            </div>
            <p class="mt-1 text-xs text-zinc-500"><b>Simular</b>: genera un resultado aleatorio para probar el ranking. <b>Reiniciar</b>: marcador a 0 y reabre predicciones. Ambos conservan las predicciones.</p>
        </section>

        <!-- Current live state -->
        <LiveBoard ref="board" :quiniela-id="q.id" />

        <!-- Register event -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-zinc-400">Registrar jugada</h2>
            <div class="space-y-3">
                <SegmentedControl v-model="eventForm.type" :options="[{value:'goal',label:'⚽ Gol'},{value:'red_card',label:'🟥 Roja'},{value:'penalty',label:'🎯 Penal'}]" />
                <SegmentedControl v-model="eventForm.team" :options="[{value:'home',label:q.home_team},{value:'away',label:q.away_team}]" />
                <select v-if="eventForm.type === 'goal'" v-model="eventForm.player_id" class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm">
                    <option :value="null">Sin especificar goleador</option>
                    <option v-for="pl in rosterForTeam" :key="pl.id" :value="pl.id">{{ pl.name }}</option>
                </select>
                <div class="flex items-center gap-3">
                    <label class="text-sm text-zinc-400">Minuto</label>
                    <input v-model.number="eventForm.minute" type="number" min="1" max="130" class="w-20 rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
                    <SegmentedControl v-model="eventForm.half" :options="[{value:1,label:'1er T'},{value:2,label:'2do T'}]" class="flex-1" />
                </div>
                <button class="w-full rounded-lg bg-emerald-500 py-2.5 font-bold text-white active:scale-95" @click="addEvent">Registrar</button>
            </div>

            <ul v-if="live?.events.length" class="mt-4 space-y-1.5">
                <li v-for="ev in live.events" :key="ev.id" class="flex items-center justify-between rounded-lg bg-zinc-800/60 px-3 py-1.5 text-sm">
                    <span class="flex items-center gap-1.5">
                        <span class="font-black tabular-nums text-zinc-500">{{ ev.minute }}'</span>
                        <font-awesome-icon
                            :icon="ev.type === 'goal' ? 'fa-solid fa-futbol' : ev.type === 'red_card' ? 'fa-solid fa-square' : 'fa-solid fa-bullseye'"
                            :class="ev.type === 'red_card' ? 'text-rose-500' : ev.type === 'penalty' ? 'text-amber-400' : 'text-zinc-100'"
                        />
                        {{ ev.player_name || (ev.team === 'home' ? q.home_team : q.away_team) }}
                    </span>
                    <button class="text-rose-400" @click="removeEvent(ev.id)"><font-awesome-icon icon="fa-solid fa-xmark" /></button>
                </li>
            </ul>
        </section>

        <!-- API sync -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-zinc-400">API-Football (opcional)</h2>
            <button class="w-full rounded-lg bg-zinc-800 py-2.5 font-bold text-zinc-200 active:scale-95" @click="syncApi"><font-awesome-icon icon="fa-solid fa-rotate" class="mr-1" /> Sincronizar marcador</button>
            <p class="mt-2 text-xs text-zinc-500">Requiere API key y fixture id configurados. El goleador igual se asigna a mano.</p>
        </section>

        <!-- Scoring rules -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-zinc-400">Puntajes</h2>
            <ul class="space-y-2">
                <li v-for="rule in ruleDraft" :key="rule.category" class="flex items-center gap-3">
                    <input type="checkbox" v-model="rule.enabled" class="h-5 w-5 accent-red-500" />
                    <span class="flex-1 text-sm" :class="!rule.enabled && 'text-zinc-500 line-through'">{{ rule.label }}</span>
                    <input v-model.number="rule.points" type="number" min="0" max="100" class="w-16 rounded-lg border border-zinc-700 bg-zinc-900 px-2 py-1 text-center text-sm" />
                </li>
            </ul>
            <button class="mt-3 w-full rounded-lg bg-red-500 py-2.5 font-bold text-white active:scale-95" @click="saveRules">Guardar puntajes</button>
        </section>

        <!-- Roster -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-zinc-400">Plantillas</h2>
            <div class="grid grid-cols-2 gap-4">
                <div v-for="team in ['home','away']" :key="team">
                    <p class="mb-1 text-sm font-bold">{{ team === 'home' ? q.home_team : q.away_team }}</p>
                    <ul class="space-y-1">
                        <li v-for="pl in q.roster[team]" :key="pl.id" class="flex items-center justify-between rounded bg-zinc-800/50 px-2 py-1 text-xs">
                            <span>{{ pl.name }}</span>
                            <button class="text-rose-400" @click="removePlayer(team, pl.id)">✕</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <select v-model="newPlayer.team" class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-2 py-2 text-sm sm:w-auto">
                    <option value="home">{{ q.home_team }}</option>
                    <option value="away">{{ q.away_team }}</option>
                </select>
                <div class="flex flex-1 gap-2">
                    <input v-model="newPlayer.name" placeholder="Nombre jugador" class="min-w-0 flex-1 rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm" />
                    <input v-model="newPlayer.number" placeholder="#" class="w-12 shrink-0 rounded-lg border border-zinc-700 bg-zinc-900 px-2 py-2 text-center text-sm" />
                    <button class="shrink-0 rounded-lg bg-emerald-500 px-4 font-bold text-white active:scale-95" @click="addPlayer"><font-awesome-icon icon="fa-solid fa-plus" /></button>
                </div>
            </div>
        </section>

        <div v-if="toast" class="fixed inset-x-0 bottom-6 z-50 mx-auto w-fit rounded-full bg-zinc-100 px-4 py-2 text-sm font-bold text-zinc-900 shadow-lg">
            {{ toast }}
        </div>
    </div>
</template>
