<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Home" />

    <main class="min-h-screen flex items-center justify-center px-6">
        <div class="max-w-xl text-center">
            <p class="text-sm font-medium uppercase tracking-widest text-gray-400">
                Travel Gear Co
            </p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">
                Inertia + Vue 3 is wired up.
            </h1>
            <p class="mt-6 text-lg text-gray-600">
                Laravel {{ laravelVersion }} on PHP {{ phpVersion }}, with Tailwind CSS
                and a MySQL database. Ready to build the storefront.
            </p>
        </div>
    </main>
</template>
