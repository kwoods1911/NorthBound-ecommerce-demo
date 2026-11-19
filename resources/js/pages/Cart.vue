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
        <h1 class="font-serif text-3xl font-semibold tracking-tight">Your cart</h1>

        <div v-if="cart.lines.length" class="mt-8 lg:grid lg:grid-cols-[1fr_20rem] lg:gap-12">
            <div class="divide-y divide-stone border-y border-stone">
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
                <div class="rounded-lg bg-stone/20 p-6">
                    <OrderSummary :subtotal="cart.subtotal" />
                    <p class="mt-3 text-xs text-ink/50">Shipping and tax calculated at checkout.</p>
                    <Link
                        href="/checkout"
                        class="mt-6 block rounded-md bg-compass py-3 text-center text-sm font-medium text-white hover:bg-compass/90"
                    >
                        Checkout
                    </Link>
                </div>
            </aside>
        </div>

        <div v-else class="mt-12 rounded-lg border border-dashed border-stone py-20 text-center">
            <p class="text-sm text-ink/50">Your cart is empty.</p>
            <Link href="/new-arrivals" class="mt-3 inline-block text-sm text-ink underline">
                Browse new arrivals
            </Link>
        </div>
    </div>
</template>
