<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineOptions({ layout: AuthLayout });

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />

    <h1 class="text-2xl font-semibold tracking-tight">Log in</h1>
    <p class="mt-1 text-sm text-gray-500">
        New here?
        <Link href="/register" class="text-gray-900 underline">Create an account</Link>
    </p>

    <p v-if="status" class="mt-4 text-sm font-medium text-green-600">{{ status }}</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="email" class="block text-sm text-gray-700">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                required
                autofocus
                class="mt-1 w-full rounded-md border-gray-300 text-sm"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
            <label for="password" class="block text-sm text-gray-700">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                required
                class="mt-1 w-full rounded-md border-gray-300 text-sm"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input v-model="form.remember" type="checkbox" class="rounded border-gray-300" />
            Remember me
        </label>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-md bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-700 disabled:opacity-50"
        >
            {{ form.processing ? 'Logging in…' : 'Log in' }}
        </button>
    </form>
</template>
