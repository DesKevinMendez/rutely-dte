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
                {
                    path: '',
                    name: 'login',
                    component: () =>
                        import('@/modules/auth/views/LoginView.vue'),
                },
            ],
        },
        {
            path: '/recovery',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                {
                    path: '',
                    name: 'recovery',
                    component: () =>
                        import('@/modules/auth/views/RecoveryPasswordView.vue'),
                },
            ],
        },
        {
            path: '/onboarding',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                {
                    path: '',
                    name: 'onboarding',
                    component: () =>
                        import('@/guess/modules/onboarding/views/OnboardingView.vue'),
                },
            ],
        },
        {
            path: '/',
            component: () => import('@/layouts/LayoutAuthenticated.vue'),
            meta: { requiresAuth: true },
            children: [
                { path: '', redirect: { name: 'dashboard' } },
                {
                    path: 'dashboard',
                    name: 'dashboard',
                    component: () =>
                        import('@/modules/dashboard/views/DashboardView.vue'),
                },
                {
                    path: 'dtes',
                    name: 'dtes',
                    component: () =>
                        import('@/modules/dtes/views/DtesView.vue'),
                },
                {
                    path: 'contingency',
                    name: 'contingency',
                    component: () =>
                        import('@/modules/contingency/views/ContingencyView.vue'),
                },
                {
                    path: 'queue',
                    name: 'queue',
                    component: () =>
                        import('@/modules/queue/views/QueueView.vue'),
                },
                {
                    path: 'certificates',
                    name: 'certificates',
                    component: () =>
                        import('@/modules/certificates/views/CertificatesView.vue'),
                },
                {
                    path: 'mh-credentials',
                    name: 'mh-credentials',
                    component: () =>
                        import('@/modules/mh-credentials/views/MhCredentialsView.vue'),
                },
                {
                    path: 'tokens',
                    name: 'tokens',
                    component: () =>
                        import('@/modules/tokens/views/ApiTokensView.vue'),
                },
                {
                    path: 'my-profile',
                    name: 'my-profile',
                    component: () =>
                        import('@/modules/profile/views/MyProfileView.vue'),
                },
            ],
        },
        {
            path: '/superadmin',
            component: () => import('@/layouts/LayoutSuperadmin.vue'),
            meta: { requiresAuth: true, superadminOnly: true },
            children: [
                { path: '', redirect: { name: 'superadmin-dashboard' } },
                {
                    path: 'dashboard',
                    name: 'superadmin-dashboard',
                    component: () =>
                        import('@/modules/superadmin/views/SuperadminDashboardView.vue'),
                },
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

    const isSuperadminRoute = to.matched.some(
        (matchedRoute) => matchedRoute.meta.superadminOnly === true,
    );

    if (
        to.meta.requiresAuth &&
        auth.user?.role === 'superadmin' &&
        !isSuperadminRoute
    ) {
        return { name: 'superadmin-dashboard' };
    }

    if (isSuperadminRoute && auth.user?.role !== 'superadmin') {
        return { name: 'dashboard' };
    }

    if (
        (to.name === 'login' || to.name === 'recovery') &&
        auth.isAuthenticated &&
        (await ensureSession())
    ) {
        if (auth.user?.role === 'superadmin') {
            return { name: 'superadmin-dashboard' };
        }

        return { name: auth.user?.company_id ? 'dashboard' : 'onboarding' };
    }

    return true;
});

export default router;
