<script setup>
import { onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import OrderSummary from '@/components/cart/OrderSummary.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    order: { type: Object, required: true },
});

let tries = 0;
let timer = null;

onMounted(() => {
    if (props.order.status === 'pending') {
        timer = setInterval(() => {
            if (tries++ >= 5 || props.order.status !== 'pending') {
                clearInterval(timer);
                return;
            }
            router.reload({ only: ['order'] });
        }, 2000);
    }
});

onUnmounted(() => timer && clearInterval(timer));
</script>

<template>
    <Head title="Order confirmed" />

    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
        <p class="text-sm font-medium uppercase tracking-widest text-gray-400">
            Order {{ order.orderNumber }}
        </p>

        <h1 class="mt-2 text-3xl font-semibold tracking-tight">
            <template v-if="order.status === 'paid'">Thank you — your order is confirmed.</template>
            <template v-else-if="order.status === 'pending'">Confirming your payment…</template>
            <template v-else>Order {{ order.statusLabel.toLowerCase() }}</template>
        </h1>

        <p v-if="order.status === 'pending'" class="mt-3 text-sm text-gray-500">
            This usually takes a few seconds. This page will update automatically.
        </p>
        <p v-else class="mt-3 text-sm text-gray-600">
            A confirmation has been sent to {{ order.email }}.
        </p>

        <div class="mt-10 divide-y divide-gray-100 border-y border-gray-100">
            <div v-for="item in order.items" :key="item.name" class="flex justify-between py-4 text-sm">
                <span>
                    {{ item.name }}
                    <span v-if="item.variantLabel" class="text-gray-500">— {{ item.variantLabel }}</span>
                    <span class="text-gray-500"> &times; {{ item.quantity }}</span>
                </span>
                <span>{{ item.lineTotal.formatted }}</span>
            </div>
        </div>

        <div class="mt-6">
            <OrderSummary
                :subtotal="order.totals.subtotal"
                :shipping="order.totals.shipping"
                :tax="order.totals.tax"
                :total="order.totals.total"
            />
        </div>

        <div class="mt-10 text-sm text-gray-600">
            <p class="font-medium text-gray-900">Shipping to</p>
            <p class="mt-1">{{ order.shipping.name }}</p>
            <p>{{ order.shipping.line1 }}<span v-if="order.shipping.line2">, {{ order.shipping.line2 }}</span></p>
            <p>{{ order.shipping.city }}<span v-if="order.shipping.state">, {{ order.shipping.state }}</span> {{ order.shipping.postalCode }}</p>
            <p>{{ order.shipping.country }}</p>
        </div>

        <Link href="/new-arrivals" class="mt-10 inline-block text-sm text-gray-900 underline">
            Continue shopping
        </Link>
    </div>
</template>
