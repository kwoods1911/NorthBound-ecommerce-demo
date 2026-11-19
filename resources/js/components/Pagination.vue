<script setup>
defineProps({
    meta: { type: Object, required: true },
});

const emit = defineEmits(['navigate']);

function pageFromUrl(url) {
    const value = new URL(url, window.location.origin).searchParams.get('page');
    return value ? Number(value) : 1;
}
</script>

<template>
    <nav
        v-if="meta.last_page > 1"
        class="mt-12 flex items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <button
            v-for="link in meta.links"
            :key="link.label"
            type="button"
            :disabled="!link.url || link.active"
            class="min-w-9 rounded-md px-3 py-2 text-sm transition-colors"
            :class="[
                link.active ? 'bg-compass text-white' : 'text-ink/70 hover:bg-stone/30',
                !link.url ? 'cursor-not-allowed opacity-40' : '',
            ]"
            @click="link.url && emit('navigate', pageFromUrl(link.url))"
            v-html="link.label"
        />
    </nav>
</template>
