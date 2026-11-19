<script setup>
import { computed } from 'vue';

const props = defineProps({
    filters: { type: Object, required: true },
    options: { type: Object, required: true },
});

const emit = defineEmits(['remove-color', 'set']);

const chips = computed(() => {
    const list = [];

    if (props.filters.category) {
        const match = (props.options.categories ?? []).find(
            (c) => c.slug === props.filters.category,
        );
        list.push({ key: 'category', label: match?.name ?? props.filters.category });
    }

    for (const color of props.filters.colors ?? []) {
        list.push({ key: `color:${color}`, label: color, color });
    }

    if (props.filters.price) {
        const match = (props.options.priceBuckets ?? []).find(
            (b) => b.key === props.filters.price,
        );
        list.push({ key: 'price', label: match?.label ?? props.filters.price });
    }

    if (props.filters.sale) {
        list.push({ key: 'sale', label: 'On sale' });
    }

    return list;
});

function remove(chip) {
    if (chip.color) {
        emit('remove-color', chip.color);
    } else if (chip.key === 'category') {
        emit('set', { category: null });
    } else if (chip.key === 'price') {
        emit('set', { price: null });
    } else if (chip.key === 'sale') {
        emit('set', { sale: null });
    }
}
</script>

<template>
    <div v-if="chips.length" class="mb-6 flex flex-wrap gap-2">
        <button
            v-for="chip in chips"
            :key="chip.key"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full bg-stone/20 px-3 py-1 text-xs text-ink/70 hover:bg-stone/40"
            @click="remove(chip)"
        >
            {{ chip.label }}
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</template>
