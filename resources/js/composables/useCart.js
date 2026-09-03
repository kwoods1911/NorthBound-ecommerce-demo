import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Cart mutations via Inertia partial reloads. Each call tracks its own busy
 * flag in `processing` (keyed) so an individual row's stepper/button can
 * disable without freezing the whole page.
 */
export function useCart() {
    const processing = reactive({});

    function visit(method, url, data, key) {
        processing[key] = true;
        router[method](url, data, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                processing[key] = false;
            },
        });
    }

    function add({ productId, variantId = null, quantity = 1 }) {
        visit('post', '/cart', { product_id: productId, variant_id: variantId, quantity }, 'add');
    }

    function updateItem(itemId, quantity) {
        visit('patch', `/cart/items/${itemId}`, { quantity }, `item:${itemId}`);
    }

    function removeItem(itemId) {
        visit('delete', `/cart/items/${itemId}`, {}, `item:${itemId}`);
    }

    return { processing, add, updateItem, removeItem };
}
