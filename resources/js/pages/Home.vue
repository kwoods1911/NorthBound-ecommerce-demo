<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import HeroSection from '@/components/home/HeroSection.vue';
import CollectionTiles from '@/components/home/CollectionTiles.vue';
import FeaturedGrid from '@/components/home/FeaturedGrid.vue';
import TrustStrip from '@/components/home/TrustStrip.vue';
import EditorialBreak from '@/components/home/EditorialBreak.vue';
import NewsletterSignup from '@/components/home/NewsletterSignup.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    hero: { type: Object, required: true },
    collectionTiles: { type: Array, required: true },
    featuredProducts: { type: Object, required: true },
    support: { type: Object, required: true },
});
</script>

<template>
    <Head title="Travel gear & luggage built to move" />

    <HeroSection :hero="props.hero" />
    <CollectionTiles :tiles="props.collectionTiles" />
    <FeaturedGrid :products="props.featuredProducts.data" />
    <TrustStrip :support="props.support" />
    <EditorialBreak />
    <NewsletterSignup />
</template>
