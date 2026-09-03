<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ChatPanel from '@/components/chat/ChatPanel.vue';

const page = usePage();
const cartCount = computed(() => page.props.cart?.count ?? 0);

// Persistent layout: header + nav + footer + chat panel, all of which survive
// navigation because the layout instance is reused across visits.
const collections = [
    { label: 'New Arrivals', href: '/new-arrivals' },
    { label: 'Best Sellers', href: '/best-sellers' },
    { label: 'Accessories', href: '/accessories' },
    { label: 'Deals', href: '/deals' },
];
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white text-gray-900">
        <header class="border-b border-gray-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-lg font-semibold tracking-tight">
                    Travel&nbsp;Gear&nbsp;Co
                </Link>

                <nav class="hidden gap-6 md:flex">
                    <Link
                        v-for="item in collections"
                        :key="item.href"
                        :href="item.href"
                        prefetch
                        class="text-sm text-gray-600 transition-colors hover:text-gray-900"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <Link
                    href="/cart"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900"
                    aria-label="Cart"
                >
                    Cart
                    <span
                        v-if="cartCount > 0"
                        class="rounded-full bg-gray-900 px-1.5 text-xs font-medium text-white"
                    >
                        {{ cartCount }}
                    </span>
                </Link>
            </div>

            <nav class="flex gap-4 overflow-x-auto border-t border-gray-100 px-4 py-2 md:hidden">
                <Link
                    v-for="item in collections"
                    :key="item.href"
                    :href="item.href"
                    prefetch
                    class="whitespace-nowrap text-sm text-gray-600"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-gray-100 py-8">
            <div class="mx-auto max-w-7xl px-4 text-sm text-gray-400 sm:px-6 lg:px-8">
                Demo store — travel gear &amp; luggage.
            </div>
        </footer>

        <ChatPanel />
    </div>
</template>
