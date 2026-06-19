<script setup>
import { ref, reactive, computed, watch } from 'vue';
import api from '../lib/api';
import Stepper from './Stepper.vue';
import SegmentedControl from './SegmentedControl.vue';
import PredictionDetail from './PredictionDetail.vue';

const props = defineProps({
    quiniela: { type: Object, required: true },
});
const emit = defineEmits(['saved']);

const p = props.quiniela.my_prediction;
const form = reactive({
    exact_home: p?.exact_home ?? 0,
    exact_away: p?.exact_away ?? 0,
    ht_home: p?.ht_home ?? 0,
    ht_away: p?.ht_away ?? 0,
    first_scoring_team: p?.first_scoring_team ?? 'home',
    first_scorer_player_id: p?.first_scorer_player_id ?? null,
    red_card: p?.red_card ?? false,
    penalty: p?.penalty ?? false,
    first_goal_minute: p?.first_goal_minute ?? 15,
    boost_category: p?.boost_category ?? null,
});

const saving = ref(false);
const error = ref('');
const errors = ref({});
const saved = ref(false);

const totalGoals = computed(() => form.exact_home + form.exact_away);
const enabledRules = computed(() => props.quiniela.rules.filter((r) => r.enabled));

const teamOptions = computed(() => [
    { value: 'home', label: props.quiniela.home_team },
    { value: 'none', label: 'Sin goles' },
    { value: 'away', label: props.quiniela.away_team },
]);
const yesNo = [
    { value: true, label: 'Sí' },
    { value: false, label: 'No' },
];

// Keep the "first goal" fields coherent with the predicted scoreline.
watch(totalGoals, (total) => {
    if (total === 0) {
        form.first_scoring_team = 'none';
        form.first_scorer_player_id = null;
    } else {
        if (form.first_scoring_team === 'none') form.first_scoring_team = 'home';
        if (!form.first_goal_minute) form.first_goal_minute = 15;
    }
});
// HT score can't exceed full-time score.
watch(() => form.exact_home, (v) => { if (form.ht_home > v) form.ht_home = v; });
watch(() => form.exact_away, (v) => { if (form.ht_away > v) form.ht_away = v; });

function toggleBoost(category) {
    form.boost_category = form.boost_category === category ? null : category;
}

// Live mirror of the form, shaped like a Prediction so we can reuse
// PredictionDetail to show answers filling in as the user types.
const scorerName = computed(() => {
    if (!form.first_scorer_player_id) return null;
    const roster = [...props.quiniela.roster.home, ...props.quiniela.roster.away];
    return roster.find((pl) => pl.id === form.first_scorer_player_id)?.name ?? null;
});
const previewPrediction = computed(() => ({
    exact_home: form.exact_home,
    exact_away: form.exact_away,
    ht_home: form.ht_home,
    ht_away: form.ht_away,
    first_scoring_team: form.first_scoring_team,
    first_scorer_player_id: form.first_scorer_player_id,
    first_scorer_name: scorerName.value,
    red_card: form.red_card,
    penalty: form.penalty,
    first_goal_minute: form.first_goal_minute,
    boost_category: form.boost_category,
    points_breakdown: null,
}));

async function submit() {
    saving.value = true;
    error.value = '';
    errors.value = {};
    saved.value = false;
    try {
        const { data } = await api.put(`/quinielas/${props.quiniela.id}/prediction`, form);
        saved.value = true;
        emit('saved', data.my_prediction);
        setTimeout(() => (saved.value = false), 2500);
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        error.value = e.response?.data?.message || 'No pudimos guardar tu predicción.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <!-- Final score -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h3 class="mb-4 text-center text-sm font-bold uppercase tracking-wide text-zinc-400">Marcador final</h3>
            <div class="flex items-start justify-around">
                <div class="flex flex-col items-center gap-2">
                    <span class="text-3xl">{{ quiniela.home_flag }}</span>
                    <span class="text-xs font-semibold">{{ quiniela.home_team }}</span>
                    <Stepper v-model="form.exact_home" />
                </div>
                <span class="pt-10 text-2xl font-black text-zinc-600">:</span>
                <div class="flex flex-col items-center gap-2">
                    <span class="text-3xl">{{ quiniela.away_flag }}</span>
                    <span class="text-xs font-semibold">{{ quiniela.away_team }}</span>
                    <Stepper v-model="form.exact_away" />
                </div>
            </div>
        </section>

        <!-- Half-time score -->
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <h3 class="mb-4 text-center text-sm font-bold uppercase tracking-wide text-zinc-400">Marcador al medio tiempo</h3>
            <div class="flex items-start justify-around">
                <Stepper v-model="form.ht_home" :max="form.exact_home" />
                <span class="pt-2 text-2xl font-black text-zinc-600">:</span>
                <Stepper v-model="form.ht_away" :max="form.exact_away" />
            </div>
        </section>

        <!-- First goal -->
        <section class="space-y-4 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
            <div>
                <h3 class="mb-2 text-sm font-bold text-zinc-300">¿Quién marca primero?</h3>
                <SegmentedControl v-model="form.first_scoring_team" :options="teamOptions" />
            </div>

            <div v-if="totalGoals > 0">
                <h3 class="mb-2 text-sm font-bold text-zinc-300">Jugador del primer gol</h3>
                <select
                    v-model="form.first_scorer_player_id"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-3 text-base outline-none focus:border-red-500"
                >
                    <option :value="null">No sé / lo dejo en blanco</option>
                    <optgroup :label="quiniela.home_team">
                        <option v-for="pl in quiniela.roster.home" :key="pl.id" :value="pl.id">
                            {{ pl.name }}{{ pl.number ? ` (${pl.number})` : '' }}
                        </option>
                    </optgroup>
                    <optgroup :label="quiniela.away_team">
                        <option v-for="pl in quiniela.roster.away" :key="pl.id" :value="pl.id">
                            {{ pl.name }}{{ pl.number ? ` (${pl.number})` : '' }}
                        </option>
                    </optgroup>
                </select>
            </div>

            <div v-if="totalGoals > 0">
                <h3 class="mb-2 text-sm font-bold text-zinc-300">
                    <font-awesome-icon icon="fa-solid fa-clock" class="mr-1 text-zinc-500" />
                    Minuto del primer gol
                </h3>
                <div class="flex items-center justify-center gap-1">
                    <Stepper v-model="form.first_goal_minute" :min="1" :max="120" />
                    <span class="text-2xl font-black text-zinc-500">'</span>
                </div>
            </div>
        </section>

        <!-- Yes/No extras -->
        <section class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
                <h3 class="mb-2 text-sm font-bold text-zinc-300">
                    <font-awesome-icon icon="fa-solid fa-square" class="mr-1 text-rose-500" /> Tarjeta roja
                </h3>
                <SegmentedControl v-model="form.red_card" :options="yesNo" />
            </div>
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
                <h3 class="mb-2 text-sm font-bold text-zinc-300">
                    <font-awesome-icon icon="fa-solid fa-bullseye" class="mr-1 text-amber-400" /> Penal
                </h3>
                <SegmentedControl v-model="form.penalty" :options="yesNo" />
            </div>
        </section>

        <!-- Live summary: answers fill in as the form is completed -->
        <PredictionDetail
            :prediction="previewPrediction"
            :home-team="quiniela.home_team"
            :away-team="quiniela.away_team"
            :show-points="false"
            heading="Así va tu predicción"
        />

        <!-- x2 wildcard + scoring guide -->
        <section class="rounded-2xl border border-red-500/30 bg-red-500/5 p-4">
            <h3 class="text-sm font-bold text-red-300">
                <font-awesome-icon icon="fa-solid fa-bolt" class="mr-1" /> Comodín x2
            </h3>
            <p class="mb-3 text-xs text-zinc-400">
                Toca una categoría para duplicar los puntos que ganes en ella. Solo puedes elegir una.
            </p>
            <ul class="space-y-1.5">
                <li v-for="rule in enabledRules" :key="rule.category">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-2 py-2 text-left active:scale-[0.99]"
                        :class="form.boost_category === rule.category ? 'bg-red-500/20 ring-1 ring-red-500/40' : ''"
                        @click="toggleBoost(rule.category)"
                    >
                        <span class="text-sm">{{ rule.label }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs font-bold text-zinc-400">{{ rule.points }} pts</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-black"
                                :class="form.boost_category === rule.category
                                    ? 'bg-red-500 text-white'
                                    : 'bg-zinc-800 text-zinc-400'"
                            >x2</span>
                        </span>
                    </button>
                </li>
            </ul>
        </section>

        <p v-if="error" class="rounded-lg bg-rose-500/10 px-3 py-2 text-sm text-rose-300">{{ error }}</p>
        <ul v-if="Object.keys(errors).length" class="rounded-lg bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
            <li v-for="(msgs, field) in errors" :key="field">{{ msgs[0] }}</li>
        </ul>

        <div class="sticky bottom-3 z-10">
            <button
                type="submit" :disabled="saving"
                class="w-full rounded-xl py-3.5 text-base font-bold text-white shadow-lg shadow-red-500/20 active:scale-[0.98] disabled:opacity-60"
                :class="saved ? 'bg-emerald-500' : 'bg-red-500'"
            >
                <font-awesome-icon v-if="saved" icon="fa-solid fa-circle-check" class="mr-1" />
                {{ saved ? 'Predicción guardada' : (saving ? 'Guardando…' : 'Guardar predicción') }}
            </button>
        </div>
    </form>
</template>
