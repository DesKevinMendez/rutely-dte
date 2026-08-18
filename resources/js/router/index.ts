import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthUser } from '@/core/composables/useAuthUser';

const hasAuthToken = (): boolean =>
    typeof localStorage !== 'undefined' && Boolean(localStorage.getItem('auth_token'));

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/',
            redirect: () => ({ name: hasAuthToken() ? 'dashboard' : 'login' }),
        },
        {
            path: '/login',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                {
                    path: '',
                    name: 'login',
                    component: () => import('@/guess/modules/login/views/LoginView.vue'),
                },
            ],
        },
        {
            path: '/onboarding',
            component: () => import('@/layouts/LayoutGuest.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'onboarding',
                    component: () => import('@/modules/onboarding/views/OnboardingView.vue'),
                },
            ],
        },
        {
            path: '/dashboard',
            component: () => import('@/layouts/LayoutAuthenticated.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/modules/dashboard/views/DashboardView.vue'),
                },
            ],
        },
    ],
});

router.beforeEach(async (to) => {
    const { ensureSession, user } = useAuthUser();

    if (to.meta.requiresAuth && !(await ensureSession())) {
        return { name: 'login' };
    }

    if (to.name === 'login' && hasAuthToken() && await ensureSession()) {
        return { name: user.value?.company_id ? 'dashboard' : 'onboarding' };
    }

    return true;
});

export default router;
