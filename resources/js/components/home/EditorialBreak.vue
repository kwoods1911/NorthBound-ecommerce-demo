<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="bg-harbor text-fog">
        <div class="mx-auto max-w-3xl px-4 py-24 text-center sm:px-6 lg:px-8">
            <h2 class="font-serif text-3xl font-semibold tracking-tight sm:text-4xl">
                Built for the long trip
            </h2>
            <p class="mx-auto mt-6 max-w-xl text-lg text-fog/80">
                We make luggage the way it used to be made — hard-wearing shells, brass
                hardware, and lining you can actually clean. Fewer pieces, each one meant
                to outlast the trips you take with it.
            </p>
            <Link
                href="/about"
                class="mt-8 inline-block border border-fog/60 px-7 py-3 text-sm font-medium text-fog hover:bg-fog/10"
            >
                Read our story
            </Link>
        </div>
    </section>
</template>
