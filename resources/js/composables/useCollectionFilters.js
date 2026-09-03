import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Drives collection filtering through Inertia partial reloads.
 *
 * - filter changes: preserveState + preserveScroll, only re-fetch the props the
 *   grid and filter UI need, and reset pagination to page 1.
 * - pagination: keep state, re-fetch products only, scroll back to the grid top.
 *
 * `getFilters` is a function returning the current server-echoed `filters` prop
 * so the composable always builds on the latest query, not a stale snapshot.
 */
export function useCollectionFilters(getFilters, gridAnchor) {
    const processing = ref(false);

    const current = computed(() => getFilters() ?? {});

    function visit(query, { scrollToGrid = false, only }) {
        router.get(window.location.pathname, clean(query), {
            only,
            preserveState: true,
            preserveScroll: !scrollToGrid,
            replace: true,
            onStart: () => (processing.value = true),
            onFinish: () => {
                processing.value = false;
                if (scrollToGrid) {
                    gridAnchor?.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            },
        });
    }

    /** Merge a patch of filter values and re-fetch. Always returns to page 1. */
    function setFilters(patch) {
        visit(
            { ...current.value, ...patch, page: 1 },
            { only: ['products', 'filters', 'filterOptions'] },
        );
    }

    function toggleColor(color) {
        const colors = new Set(current.value.colors ?? []);
        colors.has(color) ? colors.delete(color) : colors.add(color);
        setFilters({ colors: [...colors] });
    }

    function clearAll() {
        visit({ sort: current.value.sort }, { only: ['products', 'filters', 'filterOptions'] });
    }

    function goToPage(page) {
        visit(
            { ...current.value, page },
            { only: ['products', 'filters'], scrollToGrid: true },
        );
    }

    return { processing, current, setFilters, toggleColor, clearAll, goToPage };
}

function clean(query) {
    const out = {};
    for (const [key, value] of Object.entries(query)) {
        if (value === null || value === undefined || value === '' || value === false) continue;
        if (Array.isArray(value) && value.length === 0) continue;
        if (key === 'page' && Number(value) <= 1) continue;
        if (key === 'sort' && value === 'featured') continue;
        out[key] = value;
    }
    return out;
}
