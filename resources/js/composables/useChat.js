import { nextTick, ref } from 'vue';

/**
 * Chatbot state. Module-level singletons so the panel keeps its conversation
 * while it lives in the persistent layout and across every page visit.
 *
 * The transcript is authoritative on the server; we hold a local copy for
 * instant rendering and hydrate it once from /chat/history on first open.
 */
const OPEN_KEY = 'chat:open';

const open = ref(readOpen());
const messages = ref([]);
const sending = ref(false);
const error = ref(null);

let hydrated = false;

function readOpen() {
    try {
        return window.localStorage.getItem(OPEN_KEY) === '1';
    } catch {
        return false;
    }
}

function persistOpen(value) {
    try {
        window.localStorage.setItem(OPEN_KEY, value ? '1' : '0');
    } catch {
        // Private mode / storage disabled — the panel just won't remember.
    }
}

async function loadHistory() {
    if (hydrated) return;
    hydrated = true;

    try {
        const { data } = await window.axios.get('/chat/history');
        messages.value = data.messages ?? [];
    } catch {
        hydrated = false; // let the next open retry
    }
}

async function sendMessage(text) {
    const content = text.trim();
    if (!content || sending.value) return;

    error.value = null;
    sending.value = true;
    messages.value.push({ id: `tmp-${Date.now()}`, role: 'user', content, products: [] });
    scrollSoon();

    try {
        const { data } = await window.axios.post('/chat', { message: content });
        messages.value.push(data.message);
    } catch (e) {
        error.value = e?.response?.status === 429
            ? 'You are sending messages very quickly — give it a moment.'
            : 'Something went wrong sending that. Please try again.';
    } finally {
        sending.value = false;
        scrollSoon();
    }
}

const scrollTarget = ref(null);

function scrollSoon() {
    nextTick(() => {
        const el = scrollTarget.value;
        if (el) el.scrollTop = el.scrollHeight;
    });
}

function setOpen(value) {
    open.value = value;
    persistOpen(value);
    if (value) loadHistory();
}

export function useChat() {
    return {
        open,
        messages,
        sending,
        error,
        scrollTarget,
        loadHistory,
        sendMessage,
        toggle: () => setOpen(!open.value),
        close: () => setOpen(false),
    };
}
