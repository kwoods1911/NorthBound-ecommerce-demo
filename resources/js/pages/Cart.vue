<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CartLineItem from '@/components/cart/CartLineItem.vue';
import OrderSummary from '@/components/cart/OrderSummary.vue';
import { useCart } from '@/composables/useCart.js';

defineOptions({ layout: AppLayout });

const props = defineProps({
    cart: { type: Object, required: true },
});

const { processing, updateItem, removeItem } = useCart();
</script>

<template>
    <Head title="Cart" />

    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-semibold tracking-tight">Your cart</h1>

        <div v-if="cart.lines.length" class="mt-8 lg:grid lg:grid-cols-[1fr_20rem] lg:gap-12">
            <div class="divide-y divide-gray-100 border-y border-gray-100">
                <CartLineItem
                    v-for="line in cart.lines"
                    :key="line.id"
                    :line="line"
                    :busy="!!processing[`item:${line.id}`]"
                    @update-quantity="updateItem(line.id, $event)"
                    @remove="removeItem(line.id)"
                />
            </div>

            <aside class="mt-10 lg:mt-0">
                <div class="rounded-lg bg-gray-50 p-6">
                    <OrderSummary :subtotal="cart.subtotal" />
                    <p class="mt-3 text-xs text-gray-500">Shipping and tax calculated at checkout.</p>
                    <Link
                        href="/checkout"
                        class="mt-6 block rounded-md bg-gray-900 py-3 text-center text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Checkout
                    </Link>
                </div>
            </aside>
        </div>

        <div v-else class="mt-12 rounded-lg border border-dashed border-gray-200 py-20 text-center">
            <p class="text-sm text-gray-500">Your cart is empty.</p>
            <Link href="/new-arrivals" class="mt-3 inline-block text-sm text-gray-900 underline">
                Browse new arrivals
            </Link>
        </div>
    </div>
</template>
