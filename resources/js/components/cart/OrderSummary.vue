<script setup>
defineProps({
    subtotal: { type: Object, required: true },
    shipping: { type: Object, default: null },
    tax: { type: Object, default: null },
    total: { type: Object, default: null },
});
</script>

<template>
    <div class="space-y-3 text-sm">
        <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span>{{ subtotal.formatted }}</span>
        </div>
        <div v-if="shipping" class="flex justify-between text-gray-600">
            <span>Shipping</span>
            <span>{{ shipping.cents === 0 ? 'Free' : shipping.formatted }}</span>
        </div>
        <div v-if="tax && tax.cents > 0" class="flex justify-between text-gray-600">
            <span>Tax</span>
            <span>{{ tax.formatted }}</span>
        </div>
        <div
            v-if="total"
            class="flex justify-between border-t border-gray-200 pt-3 text-base font-medium text-gray-900"
        >
            <span>Total</span>
            <span>{{ total.formatted }}</span>
        </div>
    </div>
</template>
