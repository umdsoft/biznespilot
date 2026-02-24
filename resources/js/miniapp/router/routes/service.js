export default [
    { path: '/', name: 'home', component: () => import('../../pages/service/ServiceHome.vue') },
    { path: '/service/:slug', name: 'service-info', component: () => import('../../pages/service/ServiceInfo.vue'), props: true },
    { path: '/search', name: 'search', component: () => import('../../pages/Search.vue') },
    { path: '/request', name: 'request-form', component: () => import('../../pages/service/RequestForm.vue') },
    { path: '/requests', name: 'my-requests', component: () => import('../../pages/service/MyRequests.vue') },
    { path: '/requests/:id', name: 'request-detail', component: () => import('../../pages/service/RequestDetail.vue'), props: true },
    { path: '/master/:id', name: 'master-profile', component: () => import('../../pages/service/MasterProfile.vue'), props: true },
]
