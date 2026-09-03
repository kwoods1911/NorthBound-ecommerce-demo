<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    product: { type: Object, required: true },
});

// Best-effort mapping of a colour name to a CSS colour for the swatch dot.
const SWATCHES = {
    black: '#111827', white: '#f9fafb', navy: '#1e293b', blue: '#3b82f6',
    sand: '#e7d3b1', beige: '#e7d3b1', grey: '#9ca3af', gray: '#9ca3af',
    green: '#16a34a', olive: '#4d7c0f', red: '#dc2626', pink: '#ec4899',
    brown: '#92400e', tan: '#d2b48c', charcoal: '#374151', silver: '#c0c0c0',
};

function swatch(name) {
    const key = String(name).toLowerCase().replace(/\s+/g, '');
    return SWATCHES[key] ?? key;
}
</script>

<template>
    <Link :href="product.url" prefetch class="group block">
        <div class="relative aspect-[4/5] overflow-hidden rounded-lg bg-gray-100">
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <span
                v-if="product.badges?.length"
                class="absolute left-3 top-3 rounded-full bg-white/90 px-2 py-0.5 text-xs font-medium text-gray-900"
            >
                {{ product.badges[0] }}
            </span>
        </div>

        <div class="mt-3 space-y-1">
            <h3 class="text-sm font-medium text-gray-900">{{ product.name }}</h3>

            <p class="text-sm">
                <span :class="product.compareAtPrice ? 'text-red-600' : 'text-gray-900'">
                    {{ product.price.formatted }}
                </span>
                <span
                    v-if="product.compareAtPrice"
                    class="ml-1.5 text-gray-400 line-through"
                >
                    {{ product.compareAtPrice.formatted }}
                </span>
            </p>

            <div v-if="product.colors?.length" class="flex gap-1 pt-1">
                <span
                    v-for="color in product.colors"
                    :key="color"
                    :title="color"
                    class="inline-block h-3 w-3 rounded-full border border-gray-300"
                    :style="{ backgroundColor: swatch(color) }"
                />
            </div>
        </div>
    </Link>
</template>
