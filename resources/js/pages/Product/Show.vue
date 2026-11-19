<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import QuantityStepper from '@/components/cart/QuantityStepper.vue';
import { useCart } from '@/composables/useCart.js';

defineOptions({ layout: AppLayout });

const props = defineProps({
    product: { type: Object, required: true },
});

const page = usePage();
const { processing, add } = useCart();

const hasVariants = computed(() => props.product.variants.length > 0);
const selectedVariant = ref(hasVariants.value ? null : { id: null });
const quantity = ref(1);

const activeImage = computed(
    () => selectedVariant.value?.image || props.product.gallery[0] || null,
);

const displayPrice = computed(
    () => selectedVariant.value?.price || props.product.price,
);

const canAdd = computed(
    () => props.product.inStock && (!hasVariants.value || selectedVariant.value),
);

const flashError = computed(() => page.props.flash?.error);

function addToCart() {
    add({
        productId: props.product.id,
        variantId: selectedVariant.value?.id ?? null,
        quantity: quantity.value,
    });
}
</script>

<template>
    <Head :title="product.name" />

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <Link :href="`/${product.category.slug}`" class="text-xs text-ink/50 hover:text-ink">
            &larr; {{ product.category.name }}
        </Link>

        <div class="mt-6 lg:grid lg:grid-cols-2 lg:gap-12">
            <div class="aspect-[4/5] overflow-hidden rounded-lg bg-stone/20">
                <img
                    v-if="activeImage"
                    :src="activeImage"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                />
            </div>

            <div class="mt-8 lg:mt-0">
                <h1 class="font-serif text-3xl font-semibold tracking-tight">{{ product.name }}</h1>

                <p class="mt-3 text-lg">
                    <span :class="product.compareAtPrice ? 'text-compass' : 'text-ink'">
                        {{ displayPrice.formatted }}
                    </span>
                    <span v-if="product.compareAtPrice" class="ml-2 text-ink/50 line-through">
                        {{ product.compareAtPrice.formatted }}
                    </span>
                </p>

                <p v-if="product.lowStock" class="mt-2 text-xs font-medium text-amber-600">
                    Low stock — order soon
                </p>
                <p v-else-if="!product.inStock" class="mt-2 text-xs font-medium text-ink/50">
                    Currently sold out
                </p>

                <div v-if="hasVariants" class="mt-6">
                    <p class="text-sm font-medium text-ink">Options</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="selectedVariant?.id === variant.id
                                ? 'border-compass bg-compass text-white'
                                : 'border-stone text-ink/70 hover:border-ink'"
                            @click="selectedVariant = variant"
                        >
                            {{ variant.label || variant.color || variant.size }}
                        </button>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-4">
                    <QuantityStepper v-model="quantity" :max="20" />
                    <button
                        type="button"
                        class="flex-1 rounded-md bg-compass py-3 text-sm font-medium text-white hover:bg-compass/90 disabled:opacity-40"
                        :disabled="!canAdd || processing.add"
                        @click="addToCart"
                    >
                        {{ processing.add ? 'Adding…' : 'Add to cart' }}
                    </button>
                </div>

                <p v-if="flashError" class="mt-3 text-sm text-red-600">{{ flashError }}</p>

                <div class="mt-8 border-t border-stone pt-6 text-sm leading-relaxed text-ink/70">
                    {{ product.description }}
                </div>
            </div>
        </div>
    </div>
</template>
