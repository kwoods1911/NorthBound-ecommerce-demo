<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import WishlistProductCard from '@/components/account/WishlistProductCard.vue';
import { useWishlist } from '@/composables/useWishlist.js';

defineOptions({ layout: AppLayout });

const props = defineProps({
    products: { type: Object, required: true },
});

const { processing, remove } = useWishlist();
</script>

<template>
    <Head title="My wishlist" />

    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <Link href="/account" class="text-xs text-ink/50 hover:text-ink">&larr; My account</Link>

        <h1 class="mt-4 font-serif text-2xl font-semibold tracking-tight">My wishlist</h1>

        <p v-if="products.data.length === 0" class="mt-6 text-sm text-ink/50">
            Your wishlist is empty.
            <Link href="/new-arrivals" class="text-ink underline">Start browsing</Link>
        </p>

        <div v-else class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <WishlistProductCard
                v-for="product in products.data"
                :key="product.id"
                :product="product"
                :removing="processing[`item:${product.id}`]"
                @remove="remove"
            />
        </div>
    </div>
</template>
