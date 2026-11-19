<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ChatPanel from '@/components/chat/ChatPanel.vue';

const page = usePage();
const cartCount = computed(() => page.props.cart?.count ?? 0);
const user = computed(() => page.props.auth?.user ?? null);

function logout() {
    router.post('/logout');
}

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
    <div class="min-h-screen flex flex-col bg-fog text-ink">
        <header class="border-b border-stone">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="font-serif text-lg font-semibold tracking-tight">
                    Travel&nbsp;Gear&nbsp;Co
                </Link>

                <nav class="hidden gap-6 md:flex">
                    <Link
                        v-for="item in collections"
                        :key="item.href"
                        :href="item.href"
                        prefetch
                        class="text-sm text-ink/70 transition-colors hover:text-ink"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-5">
                    <template v-if="user">
                        <Link href="/account" class="text-sm text-ink/70 hover:text-ink">
                            Account
                        </Link>
                        <button
                            type="button"
                            class="text-sm text-ink/70 hover:text-ink"
                            @click="logout"
                        >
                            Log out
                        </button>
                    </template>
                    <template v-else>
                        <Link href="/login" class="text-sm text-ink/70 hover:text-ink">
                            Log in
                        </Link>
                        <Link href="/register" class="hidden text-sm text-ink/70 hover:text-ink sm:inline">
                            Sign up
                        </Link>
                    </template>

                    <Link
                        href="/cart"
                        class="inline-flex items-center gap-1.5 text-sm text-ink/70 hover:text-ink"
                        aria-label="Cart"
                    >
                        Cart
                        <span
                            v-if="cartCount > 0"
                            class="rounded-full bg-compass px-1.5 text-xs font-medium text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                </div>
            </div>

            <nav class="flex gap-4 overflow-x-auto border-t border-stone px-4 py-2 md:hidden">
                <Link
                    v-for="item in collections"
                    :key="item.href"
                    :href="item.href"
                    prefetch
                    class="whitespace-nowrap text-sm text-ink/70"
                >
                    {{ item.label }}
                </Link>
                <Link v-if="user" href="/account" class="whitespace-nowrap text-sm text-ink/70">
                    Account
                </Link>
                <Link v-else href="/login" class="whitespace-nowrap text-sm text-ink/70">
                    Log in
                </Link>
            </nav>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-stone py-8">
            <div class="mx-auto max-w-7xl px-4 text-sm text-ink/50 sm:px-6 lg:px-8">
                Demo store — travel gear &amp; luggage.
            </div>
        </footer>

        <ChatPanel />
    </div>
</template>
