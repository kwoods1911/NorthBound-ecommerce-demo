<script setup>
import { Link } from '@inertiajs/vue3';

// Slim layout for register/login only — no store nav, no chat panel.
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-gray-900">
        <header class="border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-lg font-semibold tracking-tight">
                    Travel&nbsp;Gear&nbsp;Co
                </Link>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </main>

        <footer class="border-t border-gray-100 py-8">
            <div class="mx-auto max-w-7xl px-4 text-sm text-gray-400 sm:px-6 lg:px-8">
                Demo store — travel gear &amp; luggage.
            </div>
        </footer>
    </div>
</template>
