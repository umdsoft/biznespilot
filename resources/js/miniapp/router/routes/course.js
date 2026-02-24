export default [
    { path: '/', name: 'home', component: () => import('../../pages/course/CourseHome.vue') },
    { path: '/category/:id', name: 'category', component: () => import('../../pages/Category.vue'), props: true },
    { path: '/course/:slug', name: 'course-detail', component: () => import('../../pages/course/CourseDetail.vue'), props: true },
    { path: '/search', name: 'search', component: () => import('../../pages/Search.vue') },
    { path: '/cart', name: 'cart', component: () => import('../../pages/Cart.vue') },
    { path: '/checkout', name: 'checkout', component: () => import('../../pages/Checkout.vue') },
    { path: '/orders', name: 'orders', component: () => import('../../pages/Orders.vue') },
    { path: '/orders/:number', name: 'order-detail', component: () => import('../../pages/OrderDetail.vue'), props: true },
]
