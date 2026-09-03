<script setup>
const props = defineProps({
    options: { type: Object, required: true },
    filters: { type: Object, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['set', 'toggle-color', 'clear']);

function isColorActive(color) {
    return (props.filters.colors ?? []).includes(color);
}
</script>

<template>
    <div class="space-y-8" :class="{ 'pointer-events-none opacity-50': disabled }">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Filter</h2>
            <button
                type="button"
                class="text-xs text-gray-500 underline hover:text-gray-900"
                @click="emit('clear')"
            >
                Clear all
            </button>
        </div>

        <fieldset v-if="options.categories" class="space-y-2">
            <legend class="mb-2 text-sm font-medium text-gray-900">Category</legend>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input
                    type="radio"
                    name="category"
                    :checked="!filters.category"
                    @change="emit('set', { category: null })"
                />
                All
            </label>
            <label
                v-for="category in options.categories"
                :key="category.slug"
                class="flex items-center gap-2 text-sm text-gray-600"
            >
                <input
                    type="radio"
                    name="category"
                    :checked="filters.category === category.slug"
                    @change="emit('set', { category: category.slug })"
                />
                {{ category.name }}
            </label>
        </fieldset>

        <fieldset v-if="options.colors?.length" class="space-y-2">
            <legend class="mb-2 text-sm font-medium text-gray-900">Colour</legend>
            <label
                v-for="color in options.colors"
                :key="color"
                class="flex items-center gap-2 text-sm text-gray-600"
            >
                <input
                    type="checkbox"
                    :checked="isColorActive(color)"
                    @change="emit('toggle-color', color)"
                />
                {{ color }}
            </label>
        </fieldset>

        <fieldset class="space-y-2">
            <legend class="mb-2 text-sm font-medium text-gray-900">Price</legend>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input
                    type="radio"
                    name="price"
                    :checked="!filters.price"
                    @change="emit('set', { price: null })"
                />
                Any price
            </label>
            <label
                v-for="bucket in options.priceBuckets"
                :key="bucket.key"
                class="flex items-center gap-2 text-sm text-gray-600"
            >
                <input
                    type="radio"
                    name="price"
                    :checked="filters.price === bucket.key"
                    @change="emit('set', { price: bucket.key })"
                />
                {{ bucket.label }}
            </label>
        </fieldset>

        <label
            v-if="options.showOnSaleFilter"
            class="flex items-center gap-2 text-sm text-gray-600"
        >
            <input
                type="checkbox"
                :checked="!!filters.sale"
                @change="emit('set', { sale: $event.target.checked || null })"
            />
            On sale only
        </label>
    </div>
</template>
