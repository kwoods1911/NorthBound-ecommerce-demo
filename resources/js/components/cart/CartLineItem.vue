<script setup>
import { Link } from '@inertiajs/vue3';
import QuantityStepper from './QuantityStepper.vue';

defineProps({
    line: { type: Object, required: true },
    busy: { type: Boolean, default: false },
});

const emit = defineEmits(['update-quantity', 'remove']);
</script>

<template>
    <div class="flex gap-4 py-6" :class="{ 'opacity-50': busy }">
        <div class="h-24 w-20 shrink-0 overflow-hidden rounded-md bg-stone/20">
            <img
                v-if="line.image"
                :src="line.image"
                :alt="line.name"
                class="h-full w-full object-cover"
            />
        </div>

        <div class="flex flex-1 flex-col">
            <div class="flex justify-between gap-4">
                <div>
                    <Link :href="`/products/${line.slug}`" class="text-sm font-medium text-ink">
                        {{ line.name }}
                    </Link>
                    <p v-if="line.variantLabel" class="mt-0.5 text-xs text-ink/50">
                        {{ line.variantLabel }}
                    </p>
                    <p class="mt-1 text-sm text-ink/50">{{ line.unitPrice.formatted }} each</p>
                </div>
                <p class="text-sm font-medium text-ink">{{ line.lineTotal.formatted }}</p>
            </div>

            <div class="mt-auto flex items-center justify-between pt-3">
                <QuantityStepper
                    :model-value="line.quantity"
                    :max="line.maxQuantity"
                    :disabled="busy"
                    @update:model-value="emit('update-quantity', $event)"
                />
                <button
                    type="button"
                    class="text-xs text-ink/50 underline hover:text-ink disabled:opacity-40"
                    :disabled="busy"
                    @click="emit('remove')"
                >
                    Remove
                </button>
            </div>
        </div>
    </div>
</template>
