/**
 * HR sahifalar uchun layout tanlash.
 * Agar URL /business/hr/ bilan boshlansa — BusinessLayout ishlatiladi.
 * Aks holda — HRLayout ishlatiladi.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useHRLayout() {
    const page = usePage();
    const isBusinessPanel = computed(() => page.url?.startsWith('/business/hr'));

    return { isBusinessPanel };
}
