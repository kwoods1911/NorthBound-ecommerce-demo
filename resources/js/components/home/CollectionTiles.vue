<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    tiles: { type: Array, required: true },
});
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="tile in tiles"
                :key="tile.key"
                :href="tile.url"
                prefetch
                class="group relative flex aspect-[4/5] items-end overflow-hidden border border-stone bg-stone/20"
            >
                <img
                    v-if="tile.image"
                    :src="tile.image"
                    :alt="tile.title"
                    loading="lazy"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <span class="absolute inset-0 bg-ink/25" />
                <span class="relative w-full px-4 py-4 font-serif text-lg text-fog">
                    {{ tile.title }}
                </span>
            </Link>
        </div>
    </section>
</template>
