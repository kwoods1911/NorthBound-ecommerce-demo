<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, required: true },
    removing: { type: Boolean, default: false },
});

const emit = defineEmits(['remove']);
</script>

<template>
    <div class="border border-stone p-3">
        <Link :href="product.url" prefetch class="block">
            <div class="aspect-[4/5] overflow-hidden bg-stone/20">
                <img
                    v-if="product.image"
                    :src="product.image"
                    :alt="product.name"
                    loading="lazy"
                    class="h-full w-full object-cover"
                />
            </div>

            <h3 class="mt-3 font-serif text-sm font-medium text-ink">{{ product.name }}</h3>
            <p class="text-sm text-ink">{{ product.price.formatted }}</p>
        </Link>

        <button
            type="button"
            class="mt-3 text-xs text-ink/50 underline hover:text-ink disabled:opacity-40"
            :disabled="removing"
            @click="emit('remove', props.product.id)"
        >
            {{ removing ? 'Removing…' : 'Remove' }}
        </button>
    </div>
</template>
