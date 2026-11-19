<script setup>
const props = defineProps({
    modelValue: { type: Number, required: true },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 99 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

function step(delta) {
    const next = Math.min(props.max, Math.max(props.min, props.modelValue + delta));
    if (next !== props.modelValue) emit('update:modelValue', next);
}
</script>

<template>
    <div class="inline-flex items-center rounded-md border border-stone">
        <button
            type="button"
            class="px-3 py-1 text-ink/70 disabled:opacity-40"
            :disabled="disabled || modelValue <= min"
            aria-label="Decrease quantity"
            @click="step(-1)"
        >
            &minus;
        </button>
        <span class="min-w-8 text-center text-sm tabular-nums">{{ modelValue }}</span>
        <button
            type="button"
            class="px-3 py-1 text-ink/70 disabled:opacity-40"
            :disabled="disabled || modelValue >= max"
            aria-label="Increase quantity"
            @click="step(1)"
        >
            +
        </button>
    </div>
</template>
