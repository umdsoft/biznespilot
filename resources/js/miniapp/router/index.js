import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('../pages/Home.vue'),
    },
    {
        path: '/category/:id',
        name: 'category',
        component: () => import('../pages/Category.vue'),
        props: true,
    },
    {
        path: '/product/:slug',
        name: 'product',
        component: () => import('../pages/Product.vue'),
        props: true,
    },
    {
        path: '/catalog/:slug',
        name: 'catalog-item',
        component: () => import('../pages/CatalogItem.vue'),
        props: true,
    },
    {
        path: '/search',
        name: 'search',
        component: () => import('../pages/Search.vue'),
    },
    {
        path: '/cart',
        name: 'cart',
        component: () => import('../pages/Cart.vue'),
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: () => import('../pages/Checkout.vue'),
    },
    {
        path: '/payment',
        name: 'payment',
        component: () => import('../pages/Payment.vue'),
    },
    {
        path: '/orders',
        name: 'orders',
        component: () => import('../pages/Orders.vue'),
    },
    {
        path: '/orders/:number',
        name: 'order-detail',
        component: () => import('../pages/OrderDetail.vue'),
        props: true,
    },
    // Admin panel routes (do'kon egasi uchun)
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

const router = createRouter({
    history: createWebHashHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition
        return { top: 0 }
    },
})

export default router
