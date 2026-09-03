<script setup>
import ProductCard from './ProductCard.vue';

defineProps({
    products: { type: Array, required: true },
    processing: { type: Boolean, default: false },
});
</script>

<template>
    <div class="relative">
        <div
            v-if="products.length"
            class="grid grid-cols-2 gap-x-4 gap-y-8 transition-opacity md:grid-cols-3 lg:grid-cols-4"
            :class="{ 'pointer-events-none opacity-40': processing }"
        >
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
            />
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-gray-200 py-20 text-center"
        >
            <p class="text-sm text-gray-500">No products match these filters.</p>
        </div>

        <div
            v-if="processing"
            class="pointer-events-none absolute inset-0 flex items-start justify-center pt-20"
        >
            <span class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" />
        </div>
    </div>
</template>
