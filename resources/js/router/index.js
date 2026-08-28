import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'Home',
        component: () => import('../views/Home.vue')
    },
    {
        path: '/blog/:slug',
        name: 'BlogPost',
        component: () => import('../views/BlogPost.vue')
    },
    {
        path: '/category/:slug',
        name: 'Category',
        component: () => import('../views/Category.vue')
    },
    {
        path: '/about',
        name: 'About',
        component: () => import('../views/About.vue')
    },
    {
        path: '/contact',
        name: 'Contact',
        component: () => import('../views/Contact.vue')
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
