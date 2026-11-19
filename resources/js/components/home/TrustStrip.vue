<script setup>
defineProps({
    support: { type: Object, required: true },
});
</script>

<template>
    <section class="border-y border-stone bg-fog">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-12 sm:grid-cols-3 sm:px-6 lg:px-8">
            <div>
                <h3 class="font-serif text-lg">Shipping</h3>
                <p class="mt-2 text-sm text-ink/70">{{ support.shipping }}</p>
            </div>
            <div>
                <h3 class="font-serif text-lg">Returns</h3>
                <p class="mt-2 text-sm text-ink/70">{{ support.returns }}</p>
            </div>
            <div>
                <h3 class="font-serif text-lg">Warranty</h3>
                <p class="mt-2 text-sm text-ink/70">{{ support.warranty }}</p>
            </div>
        </div>
    </section>
</template>
