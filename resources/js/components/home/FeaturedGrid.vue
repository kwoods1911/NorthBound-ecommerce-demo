<script setup>
import { Link } from '@inertiajs/vue3';
import ProductCard from '@/components/ProductCard.vue';

defineProps({
    products: { type: Array, required: true },
});
</script>

<template>
    <section v-if="products.length" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <h2 class="font-serif text-3xl font-semibold tracking-tight">Featured gear</h2>
            <Link href="/best-sellers" prefetch class="text-sm text-harbor hover:text-ink">
                View all best sellers
            </Link>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
            />
        </div>
    </section>
</template>
