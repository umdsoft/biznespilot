import { computed } from 'vue'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'

/**
 * Composable for Store panel pages.
 *
 * Resolves the correct layout component and route names based on panelType.
 * `panelType` ikkala konvensiyani ham qabul qiladi:
 *   - 'sales-head' (URL prefix konvensiyasi) — eski kod
 *   - 'saleshead' (Vue layoutComponent konvensiyasi) — yangi `detectPanelType()`
 */
export function useStorePanel(panelType = 'business') {
    // Vue konvensiyasini birlashtirish: 'saleshead' va 'sales-head' ikkalasi ham SalesHead
    const isSalesHead = panelType === 'sales-head' || panelType === 'saleshead'

    const layoutComponent = computed(() => {
        if (panelType === 'operator') return OperatorLayout
        if (isSalesHead) return SalesHeadLayout
        return BusinessLayout
    })

    /**
     * Generate store route URL with correct panel prefix.
     * Route names URL-style chiziqli konvensiyani saqlaydi: 'sales-head.store.*'
     */
    const storeRoute = (suffix, params) => {
        // Route name konvensiyasi 'sales-head.store.*' (chiziqli) saqlanadi
        const routePrefix = isSalesHead ? 'sales-head' : (panelType || 'business')
        return route(`${routePrefix}.store.${suffix}`, params)
    }

    const isBusinessPanel = panelType === 'business'
    const isOperatorPanel = panelType === 'operator'
    const isSalesHeadPanel = isSalesHead

    return {
        layoutComponent,
        storeRoute,
        isBusinessPanel,
        isOperatorPanel,
        isSalesHeadPanel,
    }
}
