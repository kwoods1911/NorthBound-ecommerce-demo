<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ShippingForm from '@/components/checkout/ShippingForm.vue';
import StripePaymentElement from '@/components/checkout/StripePaymentElement.vue';
import OrderSummary from '@/components/cart/OrderSummary.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    summary: { type: Object, required: true },
    stripeKey: { type: String, required: true },
    customer: { type: Object, default: () => ({}) },
});

const form = ref({
    email: props.customer.email ?? '',
    name: props.customer.name ?? '',
    line1: '',
    line2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'US',
});

const paymentEl = ref(null);
const errors = ref({});
const formError = ref(null);
const processing = ref(false);

async function pay() {
    processing.value = true;
    errors.value = {};
    formError.value = null;

    try {
        const cardError = await paymentEl.value.validate();
        if (cardError) {
            formError.value = cardError;
            return;
        }

        const { data } = await axios.post('/checkout', form.value);

        const confirmError = await paymentEl.value.confirm(data.clientSecret, data.confirmationUrl);
        if (confirmError) formError.value = confirmError;
        // success → Stripe redirects to data.confirmationUrl
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {};
            const first = Object.values(errors.value)[0];
            formError.value = (Array.isArray(first) ? first[0] : first) ?? e.response.data.message;
        } else {
            formError.value = 'Something went wrong. Please try again.';
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Head title="Checkout" />

    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-semibold tracking-tight">Checkout</h1>

        <p v-if="summary.issues.length" class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
            <span v-for="issue in summary.issues" :key="issue" class="block">{{ issue }}</span>
            <Link href="/cart" class="mt-1 inline-block underline">Review your cart</Link>
        </p>

        <form class="mt-8 lg:grid lg:grid-cols-[1fr_22rem] lg:gap-12" @submit.prevent="pay">
            <div class="space-y-10">
                <ShippingForm v-model="form" :errors="errors" />

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Payment</h2>
                    <div class="mt-4">
                        <StripePaymentElement
                            ref="paymentEl"
                            :stripe-key="stripeKey"
                            :amount-cents="summary.total.cents"
                            :currency="summary.total.currency.toLowerCase()"
                        />
                    </div>
                    <p v-if="formError" class="mt-3 text-sm text-red-600">{{ formError }}</p>
                </div>
            </div>

            <aside class="mt-10 lg:mt-0">
                <div class="rounded-lg bg-gray-50 p-6">
                    <ul class="mb-4 space-y-2 text-sm text-gray-600">
                        <li v-for="line in summary.lines" :key="line.id" class="flex justify-between">
                            <span>{{ line.name }} &times; {{ line.quantity }}</span>
                            <span>{{ line.lineTotal.formatted }}</span>
                        </li>
                    </ul>
                    <OrderSummary
                        :subtotal="summary.subtotal"
                        :shipping="summary.shipping"
                        :tax="summary.tax"
                        :total="summary.total"
                    />
                    <button
                        type="submit"
                        class="mt-6 w-full rounded-md bg-gray-900 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-40"
                        :disabled="processing || summary.issues.length > 0"
                    >
                        {{ processing ? 'Processing…' : `Pay ${summary.total.formatted}` }}
                    </button>
                    <p class="mt-3 text-center text-xs text-gray-400">
                        Test mode — use card 4242 4242 4242 4242
                    </p>
                </div>
            </aside>
        </form>
    </div>
</template>
