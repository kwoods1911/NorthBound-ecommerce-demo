<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    hero: { type: Object, required: true },
});

// The page's one deliberate motion moment: the hero copy settles in on load.
const revealed = ref(false);
onMounted(() => {
    requestAnimationFrame(() => {
        revealed.value = true;
    });
});

const hasCta = Boolean(props.hero.ctaLabel && props.hero.ctaUrl);
</script>

<template>
    <section class="relative isolate overflow-hidden bg-harbor text-fog">
        <img
            v-if="hero.image"
            :src="hero.image"
            :alt="hero.headline"
            class="absolute inset-0 -z-10 h-full w-full object-cover"
        />
        <div v-if="hero.image" class="absolute inset-0 -z-10 bg-ink/40" />

        <div
            class="mx-auto flex min-h-[70vh] max-w-7xl flex-col justify-end px-4 py-16 sm:px-6 sm:py-24 lg:px-8"
        >
            <div
                class="max-w-2xl transition-all duration-700 ease-out"
                :class="revealed ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            >
                <h1 class="font-serif text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ hero.headline }}
                </h1>
                <p v-if="hero.subheading" class="mt-5 max-w-xl text-lg text-fog/80">
                    {{ hero.subheading }}
                </p>
                <Link
                    v-if="hasCta"
                    :href="hero.ctaUrl"
                    class="mt-8 inline-block bg-compass px-7 py-3 text-sm font-medium text-fog"
                >
                    {{ hero.ctaLabel }}
                </Link>
            </div>
        </div>
    </section>
</template>
