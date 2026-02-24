export default [
    { path: '/', name: 'home', component: () => import('../../pages/queue/QueueHome.vue') },
    { path: '/service/:slug', name: 'service-detail', component: () => import('../../pages/queue/ServiceDetail.vue'), props: true },
    { path: '/book', name: 'booking-form', component: () => import('../../pages/queue/BookingForm.vue') },
    { path: '/booking-confirm', name: 'booking-confirm', component: () => import('../../pages/queue/BookingConfirmation.vue') },
    { path: '/bookings', name: 'my-bookings', component: () => import('../../pages/queue/MyBookings.vue') },
    { path: '/bookings/:id', name: 'booking-detail', component: () => import('../../pages/queue/BookingDetail.vue'), props: true },
]
