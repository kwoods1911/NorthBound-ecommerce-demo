<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    recentOrders: { type: Array, default: () => [] },
    orderCount: { type: Number, default: 0 },
    wishlistCount: { type: Number, default: 0 },
});

const user = computed(() => usePage().props.auth.user);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <Head title="My account" />

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">My account</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ user.name }} · {{ user.email }}
                </p>
            </div>
            <button
                type="button"
                class="text-sm text-gray-500 underline hover:text-gray-900"
                @click="logout"
            >
                Log out
            </button>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <Link
                href="/account/orders"
                class="rounded-lg border border-gray-200 p-5 transition-colors hover:border-gray-400"
            >
                <p class="text-sm font-medium text-gray-900">Order history</p>
                <p class="mt-1 text-2xl font-semibold">{{ orderCount }}</p>
                <p class="text-xs text-gray-500">{{ orderCount === 1 ? 'order' : 'orders' }} placed</p>
            </Link>

            <Link
                href="/account/wishlist"
                class="rounded-lg border border-gray-200 p-5 transition-colors hover:border-gray-400"
            >
                <p class="text-sm font-medium text-gray-900">Wishlist</p>
                <p class="mt-1 text-2xl font-semibold">{{ wishlistCount }}</p>
                <p class="text-xs text-gray-500">{{ wishlistCount === 1 ? 'item' : 'items' }} saved</p>
            </Link>
        </div>

        <section class="mt-10">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Recent orders</h2>

            <p v-if="recentOrders.length === 0" class="mt-3 text-sm text-gray-500">
                No orders yet.
                <Link href="/new-arrivals" class="text-gray-900 underline">Start shopping</Link>
            </p>

            <ul v-else class="mt-3 divide-y divide-gray-100 border-y border-gray-100">
                <li
                    v-for="order in recentOrders"
                    :key="order.orderNumber"
                    class="flex items-center justify-between py-4"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ order.orderNumber }}</p>
                        <p class="text-xs text-gray-500">{{ order.placedAt }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-900">{{ order.totals.total.formatted }}</p>
                        <p class="text-xs text-gray-500">{{ order.statusLabel }}</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
