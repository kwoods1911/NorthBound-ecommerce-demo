<script setup>
import { ref } from 'vue';

const email = ref('');
const submitted = ref(false);

// UI only — the demo does not send or store anything.
function submit() {
    if (email.value.trim() === '') {
        return;
    }
    submitted.value = true;
}
</script>

<template>
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <h2 class="font-serif text-2xl font-semibold tracking-tight sm:text-3xl">
            Travel notes, now and then
        </h2>
        <p class="mt-3 text-sm text-ink/70">
            Occasional emails on new gear and where we're taking it. No noise.
        </p>

        <p v-if="submitted" class="mt-6 text-sm text-harbor">
            Thanks — you're on the list.
        </p>
        <form
            v-else
            class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center"
            @submit.prevent="submit"
        >
            <input
                v-model="email"
                type="email"
                required
                placeholder="you@example.com"
                class="w-full border border-stone bg-fog px-4 py-3 text-sm sm:max-w-xs"
            />
            <button
                type="submit"
                class="bg-compass px-6 py-3 text-sm font-medium text-fog"
            >
                Subscribe
            </button>
        </form>
    </section>
</template>
