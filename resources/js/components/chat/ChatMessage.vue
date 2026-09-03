<script setup>
import ChatProductCard from '@/components/chat/ChatProductCard.vue';

defineProps({
    message: { type: Object, required: true },
});
</script>

<template>
    <div :class="message.role === 'user' ? 'items-end' : 'items-start'" class="flex flex-col gap-2">
        <div
            :class="message.role === 'user'
                ? 'bg-gray-900 text-white'
                : 'bg-gray-100 text-gray-900'"
            class="max-w-[85%] whitespace-pre-wrap rounded-2xl px-3.5 py-2 text-sm leading-relaxed"
        >
            {{ message.content }}
        </div>

        <div v-if="message.products?.length" class="w-full space-y-2">
            <ChatProductCard
                v-for="product in message.products"
                :key="product.id"
                :product="product"
            />
        </div>
    </div>
</template>
