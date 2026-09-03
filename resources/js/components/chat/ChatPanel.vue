<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ChatMessage from '@/components/chat/ChatMessage.vue';
import { useChat } from '@/composables/useChat';

const { open, messages, sending, error, scrollTarget, loadHistory, sendMessage, toggle, close } = useChat();

const draft = ref('');

const EXAMPLES = [
    'What carry-ons do you sell?',
    'Do you have packing cubes under $40?',
    'What is your returns policy?',
];

function submit() {
    const text = draft.value;
    draft.value = '';
    sendMessage(text);
}

function onKeydown(event) {
    if (event.key === 'Escape' && open.value) close();
}

// Keep the transcript pinned to the latest message.
watch([messages, sending], () => {
    const el = scrollTarget.value;
    if (el) requestAnimationFrame(() => (el.scrollTop = el.scrollHeight));
}, { deep: true });

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    if (open.value) loadHistory(); // panel was left open on a previous visit
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div>
        <!-- Launcher -->
        <button
            type="button"
            class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gray-900 text-white shadow-lg transition-transform hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
            :aria-expanded="open"
            aria-label="Open the shopping assistant"
            @click="toggle"
        >
            <svg v-if="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-2.474.19 5.99 5.99 0 0 0 1.418-3.06A8.05 8.05 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
            <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Panel -->
        <div
            v-show="open"
            class="fixed inset-0 z-40 flex flex-col bg-white sm:inset-auto sm:bottom-24 sm:right-5 sm:h-[600px] sm:max-h-[calc(100vh-8rem)] sm:w-96 sm:rounded-2xl sm:border sm:border-gray-200 sm:shadow-2xl"
            role="dialog"
            aria-label="Shopping assistant"
        >
            <header class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Shopping assistant</p>
                    <p class="text-xs text-gray-500">Products, shipping &amp; returns</p>
                </div>
                <button
                    type="button"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Close"
                    @click="close"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </header>

            <div
                ref="scrollTarget"
                class="flex-1 space-y-4 overflow-y-auto px-4 py-4"
                aria-live="polite"
            >
                <div v-if="!messages.length" class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Hi! Ask me about our travel gear, shipping or returns.
                    </p>
                    <div class="flex flex-col gap-2">
                        <button
                            v-for="example in EXAMPLES"
                            :key="example"
                            type="button"
                            class="rounded-lg border border-gray-200 px-3 py-2 text-left text-sm text-gray-700 hover:border-gray-300 hover:bg-gray-50"
                            @click="sendMessage(example)"
                        >
                            {{ example }}
                        </button>
                    </div>
                </div>

                <ChatMessage v-for="message in messages" :key="message.id" :message="message" />

                <div v-if="sending" class="flex items-center gap-1.5 px-1 text-gray-400">
                    <span class="h-2 w-2 animate-bounce rounded-full bg-current [animation-delay:-0.3s]" />
                    <span class="h-2 w-2 animate-bounce rounded-full bg-current [animation-delay:-0.15s]" />
                    <span class="h-2 w-2 animate-bounce rounded-full bg-current" />
                </div>
            </div>

            <p v-if="error" class="border-t border-red-100 bg-red-50 px-4 py-2 text-xs text-red-600">
                {{ error }}
            </p>

            <form class="flex items-end gap-2 border-t border-gray-100 p-3" @submit.prevent="submit">
                <textarea
                    v-model="draft"
                    rows="1"
                    placeholder="Type a message…"
                    class="max-h-28 flex-1 resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-gray-400 focus:outline-none"
                    @keydown.enter.exact.prevent="submit"
                />
                <button
                    type="submit"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-white disabled:opacity-40"
                    :disabled="sending || !draft.trim()"
                    aria-label="Send"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</template>
