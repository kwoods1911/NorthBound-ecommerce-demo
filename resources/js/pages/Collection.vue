<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';
import Pagination from '@/components/Pagination.vue';
import CollectionFilters from '@/components/Collection/CollectionFilters.vue';
import ActiveFilterChips from '@/components/Collection/ActiveFilterChips.vue';
import { useCollectionFilters } from '@/composables/useCollectionFilters.js';

defineOptions({ layout: AppLayout });

const props = defineProps({
    collection: { type: Object, required: true },
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const gridAnchor = ref(null);
const drawerOpen = ref(false);

const { processing, setFilters, toggleColor, clearAll, goToPage } = useCollectionFilters(
    () => props.filters,
    gridAnchor,
);

const sortValue = computed(() => props.filters.sort ?? 'featured');
const total = computed(() => props.products.meta?.total ?? props.products.data.length);

function onSetFilters(patch) {
    setFilters(patch);
    drawerOpen.value = false;
}
</script>

<template>
    <Head :title="collection.title" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="max-w-2xl">
            <h1 class="font-serif text-3xl font-semibold tracking-tight sm:text-4xl">
                {{ collection.title }}
            </h1>
            <p class="mt-3 text-ink/70">{{ collection.description }}</p>
        </header>

        <div class="mt-8 flex items-center justify-between gap-4 border-b border-stone pb-4">
            <p class="text-sm text-ink/50">{{ total }} {{ total === 1 ? 'product' : 'products' }}</p>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="text-sm text-ink/70 underline lg:hidden"
                    @click="drawerOpen = true"
                >
                    Filters
                </button>

                <label class="flex items-center gap-2 text-sm text-ink/70">
                    <span class="hidden sm:inline">Sort</span>
                    <select
                        class="rounded-md border-stone py-1.5 pl-2 pr-8 text-sm"
                        :value="sortValue"
                        :disabled="processing"
                        @change="setFilters({ sort: $event.target.value })"
                    >
                        <option
                            v-for="option in filterOptions.sorts"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </label>
            </div>
        </div>

        <div class="mt-8 lg:grid lg:grid-cols-[16rem_1fr] lg:gap-10">
            <aside class="hidden lg:block">
                <CollectionFilters
                    :options="filterOptions"
                    :filters="filters"
                    :disabled="processing"
                    @set="setFilters"
                    @toggle-color="toggleColor"
                    @clear="clearAll"
                />
            </aside>

            <div ref="gridAnchor" class="mt-8 lg:mt-0">
                <ActiveFilterChips
                    :filters="filters"
                    :options="filterOptions"
                    @remove-color="toggleColor"
                    @set="setFilters"
                />

                <ProductGrid :products="products.data" :processing="processing" />

                <Pagination :meta="products.meta" @navigate="goToPage" />
            </div>
        </div>
    </div>

    <!-- Mobile filter drawer -->
    <Teleport to="body">
        <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-black/30" @click="drawerOpen = false" />
            <div class="absolute inset-y-0 right-0 w-80 max-w-[85vw] overflow-y-auto bg-white p-6 shadow-xl">
                <div class="mb-6 flex items-center justify-between">
                    <span class="text-sm font-semibold">Filters</span>
                    <button type="button" class="text-ink/50" @click="drawerOpen = false">
                        Close
                    </button>
                </div>
                <CollectionFilters
                    :options="filterOptions"
                    :filters="filters"
                    :disabled="processing"
                    @set="onSetFilters"
                    @toggle-color="toggleColor"
                    @clear="clearAll"
                />
            </div>
        </div>
    </Teleport>
</template>
