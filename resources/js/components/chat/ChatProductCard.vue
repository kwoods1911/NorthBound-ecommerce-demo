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
        class="flex gap-3 rounded-lg border border-stone bg-white p-2 transition-colors hover:border-compass"
        @click="close"
    >
        <div class="h-14 w-14 shrink-0 overflow-hidden rounded bg-stone/20">
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover"
            />
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-ink">{{ product.name }}</p>
            <p class="mt-0.5 text-sm">
                <span :class="product.compareAtPrice ? 'text-compass' : 'text-ink'">
                    {{ product.price.formatted }}
                </span>
                <span v-if="product.compareAtPrice" class="ml-1.5 text-ink/50 line-through">
                    {{ product.compareAtPrice.formatted }}
                </span>
            </p>
            <p v-if="product.badges?.length" class="mt-0.5 text-xs text-ink/50">
                {{ product.badges[0] }}
            </p>
        </div>
    </Link>
</template>
