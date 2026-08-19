import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthSession } from '@/core/composables/useAuthSession';
import { useAuth } from '@/core/stores/auth';

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/login',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                { path: '', name: 'login', component: () => import('@/modules/auth/views/LoginView.vue') },
            ],
        },
        {
            path: '/recovery',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                { path: '', name: 'recovery', component: () => import('@/modules/auth/views/RecoveryPasswordView.vue') },
            ],
        },
        {
            path: '/onboarding',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                { path: '', name: 'onboarding', component: () => import('@/guess/modules/onboarding/views/OnboardingView.vue') },
            ],
        },
        {
            path: '/',
            component: () => import('@/layouts/LayoutAuthenticated.vue'),
            meta: { requiresAuth: true },
            children: [
                { path: '', redirect: { name: 'dashboard' } },
                { path: 'dashboard', name: 'dashboard', component: () => import('@/modules/dashboard/views/DashboardView.vue') },
            ],
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuth();
    const { ensureSession } = useAuthSession();

    if (to.meta.requiresAuth && !(await ensureSession())) {
        return { name: 'login' };
    }

    if ((to.name === 'login' || to.name === 'recovery') && auth.isAuthenticated && await ensureSession()) {
        return { name: auth.user?.company_id ? 'dashboard' : 'onboarding' };
    }

    return true;
});

export default router;
