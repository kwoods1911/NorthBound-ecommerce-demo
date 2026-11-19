<script setup>
defineProps({
    modelValue: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink/50">Contact & shipping</h2>

        <div>
            <label class="block text-sm text-ink/70">Email</label>
            <input
                type="email"
                class="mt-1 w-full rounded-md border-stone text-sm"
                :value="modelValue.email"
                @input="emit('update:modelValue', { ...modelValue, email: $event.target.value })"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
        </div>

        <div>
            <label class="block text-sm text-ink/70">Full name</label>
            <input
                type="text"
                class="mt-1 w-full rounded-md border-stone text-sm"
                :value="modelValue.name"
                @input="emit('update:modelValue', { ...modelValue, name: $event.target.value })"
            />
            <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
        </div>

        <div>
            <label class="block text-sm text-ink/70">Address</label>
            <input
                type="text"
                placeholder="Street address"
                class="mt-1 w-full rounded-md border-stone text-sm"
                :value="modelValue.line1"
                @input="emit('update:modelValue', { ...modelValue, line1: $event.target.value })"
            />
            <input
                type="text"
                placeholder="Apartment, suite, etc. (optional)"
                class="mt-2 w-full rounded-md border-stone text-sm"
                :value="modelValue.line2"
                @input="emit('update:modelValue', { ...modelValue, line2: $event.target.value })"
            />
            <p v-if="errors.line1" class="mt-1 text-xs text-red-600">{{ errors.line1 }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-ink/70">City</label>
                <input
                    type="text"
                    class="mt-1 w-full rounded-md border-stone text-sm"
                    :value="modelValue.city"
                    @input="emit('update:modelValue', { ...modelValue, city: $event.target.value })"
                />
                <p v-if="errors.city" class="mt-1 text-xs text-red-600">{{ errors.city }}</p>
            </div>
            <div>
                <label class="block text-sm text-ink/70">State / Region</label>
                <input
                    type="text"
                    class="mt-1 w-full rounded-md border-stone text-sm"
                    :value="modelValue.state"
                    @input="emit('update:modelValue', { ...modelValue, state: $event.target.value })"
                />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-ink/70">Postal code</label>
                <input
                    type="text"
                    class="mt-1 w-full rounded-md border-stone text-sm"
                    :value="modelValue.postal_code"
                    @input="emit('update:modelValue', { ...modelValue, postal_code: $event.target.value })"
                />
                <p v-if="errors.postal_code" class="mt-1 text-xs text-red-600">{{ errors.postal_code }}</p>
            </div>
            <div>
                <label class="block text-sm text-ink/70">Country</label>
                <input
                    type="text"
                    maxlength="2"
                    placeholder="US"
                    class="mt-1 w-full rounded-md border-stone text-sm uppercase"
                    :value="modelValue.country"
                    @input="emit('update:modelValue', { ...modelValue, country: $event.target.value.toUpperCase() })"
                />
                <p v-if="errors.country" class="mt-1 text-xs text-red-600">{{ errors.country }}</p>
            </div>
        </div>
    </div>
</template>
