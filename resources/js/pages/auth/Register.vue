<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineOptions({ layout: AuthLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Create account" />

    <h1 class="text-2xl font-semibold tracking-tight">Create your account</h1>
    <p class="mt-1 text-sm text-gray-500">
        Already have one?
        <Link href="/login" class="text-gray-900 underline">Log in</Link>
    </p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="name" class="block text-sm text-gray-700">Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                autocomplete="name"
                required
                autofocus
                class="mt-1 w-full rounded-md border-gray-300 text-sm"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label for="email" class="block text-sm text-gray-700">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                required
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
                autocomplete="new-password"
                required
                class="mt-1 w-full rounded-md border-gray-300 text-sm"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm text-gray-700">Confirm password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                class="mt-1 w-full rounded-md border-gray-300 text-sm"
            />
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-md bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-700 disabled:opacity-50"
        >
            {{ form.processing ? 'Creating account…' : 'Create account' }}
        </button>
    </form>
</template>
