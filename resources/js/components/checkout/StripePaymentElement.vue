<script setup>
import { onMounted, ref } from 'vue';
import { loadStripe } from '@stripe/stripe-js';

const props = defineProps({
    stripeKey: { type: String, required: true },
    amountCents: { type: Number, required: true },
    currency: { type: String, default: 'usd' },
});

let stripe = null;
let elements = null;
const ready = ref(false);
const mountEl = ref(null);

onMounted(async () => {
    stripe = await loadStripe(props.stripeKey);
    elements = stripe.elements({
        mode: 'payment',
        amount: props.amountCents,
        currency: props.currency,
        appearance: { theme: 'stripe', variables: { colorPrimary: '#111827' } },
    });
    elements.create('payment').mount(mountEl.value);
    ready.value = true;
});

/** Client-side validation of the card fields. Returns an error message or null. */
async function validate() {
    const { error } = await elements.submit();
    return error?.message ?? null;
}

/** Confirm the PaymentIntent; on success Stripe redirects to returnUrl. */
async function confirm(clientSecret, returnUrl) {
    const { error } = await stripe.confirmPayment({
        elements,
        clientSecret,
        confirmParams: { return_url: returnUrl },
    });
    return error?.message ?? null;
}

defineExpose({ validate, confirm, ready });
</script>

<template>
    <div>
        <div v-if="!ready" class="text-sm text-gray-500">Loading payment form…</div>
        <div ref="mountEl" />
    </div>
</template>
