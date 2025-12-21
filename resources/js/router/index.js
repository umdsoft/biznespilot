import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/auth',
    component: () => import('@/layouts/AuthLayout.vue'),
    meta: { requiresGuest: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPassword.vue'),
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard.vue'),
      },
      {
        path: '/business',
        name: 'business',
        component: () => import('@/pages/business/Index.vue'),
      },
      {
        path: '/dream-buyer',
        name: 'dream-buyer',
        component: () => import('@/pages/dream-buyer/Index.vue'),
      },
      {
        path: '/marketing',
        name: 'marketing',
        component: () => import('@/pages/marketing/Index.vue'),
      },
      {
        path: '/sales',
        name: 'sales',
        component: () => import('@/pages/sales/Index.vue'),
      },
      {
        path: '/competitors',
        name: 'competitors',
        component: () => import('@/pages/competitors/Index.vue'),
      },
      {
        path: '/offers',
        name: 'offers',
        component: () => import('@/pages/offers/Index.vue'),
      },
      {
        path: '/ai',
        name: 'ai',
        component: () => import('@/pages/ai/Index.vue'),
      },
      {
        path: '/chatbot',
        name: 'chatbot',
        component: () => import('@/pages/chatbot/Index.vue'),
      },
      {
        path: '/reports',
        name: 'reports',
        component: () => import('@/pages/reports/Index.vue'),
      },
      {
        path: '/settings',
        name: 'settings',
        component: () => import('@/pages/settings/Index.vue'),
      },
      {
        path: '/onboarding',
        name: 'onboarding',
        component: () => import('@/Pages/Onboarding/Index.vue'),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' });
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;
