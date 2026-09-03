<script setup>
import { Link } from '@inertiajs/vue3';
import { useChat } from '@/composables/useChat';

defineProps({
    product: { type: Object, required: true },
});

const { close } = useChat();
</script>

<template>
    <Link
        :href="product.url"
        class="flex gap-3 rounded-lg border border-gray-200 bg-white p-2 transition-colors hover:border-gray-300"
        @click="close"
    >
        <div class="h-14 w-14 shrink-0 overflow-hidden rounded bg-gray-100">
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover"
            />
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-gray-900">{{ product.name }}</p>
            <p class="mt-0.5 text-sm">
                <span :class="product.compareAtPrice ? 'text-red-600' : 'text-gray-900'">
                    {{ product.price.formatted }}
                </span>
                <span v-if="product.compareAtPrice" class="ml-1.5 text-gray-400 line-through">
                    {{ product.compareAtPrice.formatted }}
                </span>
            </p>
            <p v-if="product.badges?.length" class="mt-0.5 text-xs text-gray-500">
                {{ product.badges[0] }}
            </p>
        </div>
    </Link>
</template>
