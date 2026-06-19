<script setup>
// Touch-friendly number control: − / + buttons AND an editable field so the
// value can be typed directly. Used for scorelines and the first-goal minute.
const props = defineProps({
    modelValue: { type: Number, default: 0 },
    min: { type: Number, default: 0 },
    max: { type: Number, default: 30 },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

function set(v) {
    if (Number.isNaN(v)) return;
    const clamped = Math.max(props.min, Math.min(props.max, v));
    emit('update:modelValue', clamped);
}

function onInput(e) {
    // Allow the field to be empty mid-typing without forcing a value yet.
    if (e.target.value === '') return;
    set(parseInt(e.target.value, 10));
}

function onBlur(e) {
    // Snap back to a valid number on blur (handles empty / out-of-range input).
    set(e.target.value === '' ? props.min : parseInt(e.target.value, 10));
    e.target.value = props.modelValue;
}
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            type="button"
            :disabled="disabled || modelValue <= min"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-zinc-200 disabled:opacity-30 active:scale-90"
            @click="set(modelValue - 1)"
        >
            <font-awesome-icon icon="fa-solid fa-minus" />
        </button>
        <input
            type="number"
            inputmode="numeric"
            :value="modelValue"
            :min="min"
            :max="max"
            :disabled="disabled"
            class="w-14 rounded-xl bg-transparent text-center text-3xl font-black tabular-nums outline-none focus:bg-zinc-800/60"
            @input="onInput"
            @blur="onBlur"
            @focus="$event.target.select()"
        />
        <button
            type="button"
            :disabled="disabled || modelValue >= max"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-zinc-200 disabled:opacity-30 active:scale-90"
            @click="set(modelValue + 1)"
        >
            <font-awesome-icon icon="fa-solid fa-plus" />
        </button>
    </div>
</template>
