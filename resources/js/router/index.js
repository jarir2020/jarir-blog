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
        path: '/search',
        name: 'Search',
        component: () => import('../views/Search.vue')
    },
    {
        path: '/author/:username',
        name: 'Author',
        component: () => import('../views/Author.vue')
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
    },

    // Phase 4 — admin SPA. The /admin paths mount a different layout
    // (see App.vue) that does not include the public header / footer.
    //
    // The login / forbidden states are NOT separate routes here; the
    // AdminLayout hard-navigates to /login?intended=… when the user is
    // not signed in. Routing those inside the SPA led to a dead-end
    // "Go to login" button (see commit history).
    {
        path: '/admin',
        component: () => import('../views/admin/AdminLayout.vue'),
        children: [
            { path: '', name: 'AdminDashboard', component: () => import('../views/admin/AdminDashboard.vue') },
            { path: 'posts', name: 'AdminPosts', component: () => import('../views/admin/AdminPosts.vue') },
            { path: 'posts/new', name: 'AdminPostNew', component: () => import('../views/admin/AdminPostEdit.vue') },
            { path: 'posts/:id', name: 'AdminPostEdit', component: () => import('../views/admin/AdminPostEdit.vue') },
            { path: 'comments', name: 'AdminComments', component: () => import('../views/admin/AdminComments.vue') },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
