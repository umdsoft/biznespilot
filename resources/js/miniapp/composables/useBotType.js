import { computed } from 'vue'
import { botTabConfig, botAccentColors, botBottomNavRoutes, botActiveRouteGroups } from '../utils/botConfig'

let _storeType = null

export function getStoreType() {
    if (!_storeType) {
        _storeType = document.getElementById('miniapp')?.dataset?.storeType || 'ecommerce'
    }
    return _storeType
}

export function useBotType() {
    const storeType = getStoreType()

    const isEcommerce = computed(() => storeType === 'ecommerce')
    const isDelivery = computed(() => storeType === 'delivery')
    const isQueue = computed(() => storeType === 'queue')
    const isService = computed(() => storeType === 'service')
    const isCourse = computed(() => storeType === 'course')
    const hasCart = computed(() => ['ecommerce', 'delivery', 'course'].includes(storeType))

    const bottomTabs = computed(() => botTabConfig[storeType] || botTabConfig.ecommerce)
    const bottomNavRouteNames = computed(() => botBottomNavRoutes[storeType] || botBottomNavRoutes.ecommerce)
    const accentColor = computed(() => botAccentColors[storeType] || null)

    function isTabActive(tabName, currentRouteName) {
        const group = botActiveRouteGroups[tabName]
        return group ? group.includes(currentRouteName) : false
    }

    return {
        storeType,
        isEcommerce,
        isDelivery,
        isQueue,
        isService,
        isCourse,
        hasCart,
        bottomTabs,
        bottomNavRouteNames,
        accentColor,
        isTabActive,
    }
}
