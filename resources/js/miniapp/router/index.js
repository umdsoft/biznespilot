import { createRouter, createWebHashHistory } from 'vue-router'
import { getStoreType } from '../composables/useBotType'

// Shared routes (barcha bot turlari uchun)
const sharedRoutes = [
    {
        path: '/payment',
        name: 'payment',
        component: () => import('../pages/Payment.vue'),
    },
    {
        path: '/admin',
        name: 'admin-dashboard',
        component: () => import('../pages/AdminDashboard.vue'),
    },
    {
        path: '/admin/orders',
        name: 'admin-orders',
        component: () => import('../pages/AdminOrders.vue'),
    },
    {
        path: '/admin/orders/:id',
        name: 'admin-order-detail',
        component: () => import('../pages/AdminOrderDetail.vue'),
        props: true,
    },
]

// Bot-type-specific route loaders
const routesByType = {
    ecommerce: () => import('./routes/ecommerce'),
    delivery: () => import('./routes/delivery'),
    queue: () => import('./routes/queue'),
    service: () => import('./routes/service'),
}

export async function createAppRouter() {
    const storeType = getStoreType()
    const loader = routesByType[storeType] || routesByType.ecommerce
    const { default: typeRoutes } = await loader()

    return createRouter({
        history: createWebHashHistory(),
        routes: [...typeRoutes, ...sharedRoutes],
        scrollBehavior(to, from, savedPosition) {
            if (savedPosition) return savedPosition
            return { top: 0 }
        },
    })
}
