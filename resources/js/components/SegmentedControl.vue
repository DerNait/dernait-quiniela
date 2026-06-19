<script setup>
// Generic pill segmented control. options: [{ value, label }]
defineProps({
    modelValue: { type: [String, Number, Boolean, null], default: null },
    options: { type: Array, required: true },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="grid auto-cols-fr grid-flow-col gap-1 rounded-xl bg-zinc-800/60 p-1">
        <button
            v-for="opt in options"
            :key="String(opt.value)"
            type="button"
            :disabled="disabled"
            class="rounded-lg px-2 py-2 text-sm font-semibold transition active:scale-95 disabled:opacity-50"
            :class="modelValue === opt.value
                ? 'bg-red-500 text-white shadow'
                : 'text-zinc-300 hover:bg-zinc-700/50'"
            @click="emit('update:modelValue', opt.value)"
        >{{ opt.label }}</button>
    </div>
</template>
