export default [
    { path: '/', name: 'home', component: () => import('../../pages/delivery/DeliveryHome.vue') },
    { path: '/category/:id', name: 'category', component: () => import('../../pages/Category.vue'), props: true },
    { path: '/item/:slug', name: 'menu-item', component: () => import('../../pages/delivery/MenuItem.vue'), props: true },
    { path: '/search', name: 'search', component: () => import('../../pages/Search.vue') },
    { path: '/cart', name: 'cart', component: () => import('../../pages/delivery/DeliveryCart.vue') },
    { path: '/checkout', name: 'checkout', component: () => import('../../pages/Checkout.vue') },
    { path: '/orders', name: 'orders', component: () => import('../../pages/Orders.vue') },
    { path: '/orders/:number', name: 'order-detail', component: () => import('../../pages/delivery/DeliveryOrderDetail.vue'), props: true },
]
