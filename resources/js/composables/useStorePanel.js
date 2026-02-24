import { computed } from 'vue'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'

/**
 * Composable for Store panel pages.
 *
 * Resolves the correct layout component and route names based on panelType
 * ('business', 'operator', or 'sales-head').
 */
export function useStorePanel(panelType = 'business') {
    const layoutComponent = computed(() => {
        switch (panelType) {
            case 'operator': return OperatorLayout
            case 'sales-head': return SalesHeadLayout
            default: return BusinessLayout
        }
    })

    /**
     * Generate store route URL with correct panel prefix.
     * storeRoute('orders.index') → route('operator.store.orders.index')
     */
    const storeRoute = (suffix, params) => {
        const prefix = panelType || 'business'
        return route(`${prefix}.store.${suffix}`, params)
    }

    const isBusinessPanel = panelType === 'business'
    const isOperatorPanel = panelType === 'operator'
    const isSalesHeadPanel = panelType === 'sales-head'

    return {
        layoutComponent,
        storeRoute,
        isBusinessPanel,
        isOperatorPanel,
        isSalesHeadPanel,
    }
}
