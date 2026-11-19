<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
</script>

<template>
    <Head title="About" />

    <article class="mx-auto max-w-3xl px-4 py-20 sm:px-6 lg:px-8">
        <h1 class="font-serif text-4xl font-semibold tracking-tight sm:text-5xl">
            About Travel Gear Co
        </h1>

        <div class="mt-8 aspect-[16/9] w-full bg-harbor" />

        <div class="mt-10 space-y-6 text-lg text-ink/80">
            <p>
                Travel Gear Co started with a simple frustration: luggage that looked
                the part in a showroom and fell apart on the third trip. We wanted bags
                built the way they used to be — hard-wearing shells, brass hardware, and
                a lining you can actually wipe clean.
            </p>
            <p>
                So we make a small range and make it properly. A carry-on, a check bag,
                a weekender, and the packing pieces that go inside them. Each one is
                designed to be repaired rather than replaced, and covered by a limited
                lifetime warranty because we expect it to last.
            </p>
            <p>
                We're a demo storefront, but the intent is real: fewer, better things
                for people who actually go places.
            </p>
        </div>

        <div class="mt-12 flex flex-wrap gap-4 text-sm">
            <Link href="/new-arrivals" prefetch class="text-harbor hover:text-ink">
                New Arrivals
            </Link>
            <Link href="/best-sellers" prefetch class="text-harbor hover:text-ink">
                Best Sellers
            </Link>
        </div>
    </article>
</template>
