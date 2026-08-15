import { createRouter, createWebHashHistory } from 'vue-router';

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/',
            component: () => import('@/layouts/LayoutGuest.vue'),
            children: [
                {
                    path: '',
                    name: 'login',
                    component: () => import('@/guess/modules/login/views/LoginView.vue'),
                },
            ],
        },
    ],
});

export default router;
