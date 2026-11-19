import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Wishlist mutations via Inertia partial reloads, same shape as useCart.js.
 */
export function useWishlist() {
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

    function add(productId) {
        visit('post', '/wishlist', { product_id: productId }, `item:${productId}`);
    }

    function remove(productId) {
        visit('delete', `/wishlist/${productId}`, {}, `item:${productId}`);
    }

    return { processing, add, remove };
}
