import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/login', name: 'login', component: () => import('../views/LoginView.vue'), meta: { guest: true } },
    { path: '/register', name: 'register', component: () => import('../views/RegisterView.vue'), meta: { guest: true } },
    { path: '/', name: 'home', component: () => import('../views/HomeView.vue'), meta: { auth: true } },
    { path: '/quinielas/:id', name: 'quiniela', component: () => import('../views/QuinielaView.vue'), meta: { auth: true } },
    { path: '/admin', name: 'admin', component: () => import('../views/AdminHomeView.vue'), meta: { auth: true, admin: true } },
    { path: '/admin/quinielas/:id', name: 'admin.quiniela', component: () => import('../views/AdminQuinielaView.vue'), meta: { auth: true, admin: true } },
    { path: '/tv/:id', name: 'tv', component: () => import('../views/TvView.vue'), meta: { auth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (!auth.ready) {
        await auth.restore();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (to.meta.admin && !auth.isAdmin) {
        return { name: 'home' };
    }
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'home' };
    }
    return true;
});

export default router;
