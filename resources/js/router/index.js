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
        component: () => import('../views/AboutIndex.vue')
    },
    {
        // /about/:slug covers the four sub-pages (our-mission,
        // what-we-offer, our-team, contact-us). Admin-editable via
        // /admin/settings/pages.
        path: '/about/:slug',
        name: 'AboutPage',
        component: () => import('../views/PageShow.vue')
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

            // Phase 5 — taxonomy CRUD. Each entity has a list page
            // plus a combined new/edit form (the new vs edit branch
            // is selected via `route.name`, same pattern as the post
            // form). Mounted under `/admin/settings/...` so the
            // Post Settings sidebar group has a coherent URL space.
            { path: 'settings/statuses',   name: 'AdminStatuses',    component: () => import('../views/admin/AdminStatuses.vue') },
            { path: 'settings/statuses/new',   name: 'AdminStatusNew',   component: () => import('../views/admin/AdminStatusEdit.vue') },
            { path: 'settings/statuses/:id',   name: 'AdminStatusEdit',  component: () => import('../views/admin/AdminStatusEdit.vue') },
            { path: 'settings/categories', name: 'AdminCategories',  component: () => import('../views/admin/AdminCategories.vue') },
            { path: 'settings/categories/new', name: 'AdminCategoryNew', component: () => import('../views/admin/AdminCategoryEdit.vue') },
            { path: 'settings/categories/:id', name: 'AdminCategoryEdit',component: () => import('../views/admin/AdminCategoryEdit.vue') },
            { path: 'settings/tags',       name: 'AdminTags',        component: () => import('../views/admin/AdminTags.vue') },
            { path: 'settings/tags/new',       name: 'AdminTagNew',      component: () => import('../views/admin/AdminTagEdit.vue') },
            { path: 'settings/tags/:id',       name: 'AdminTagEdit',     component: () => import('../views/admin/AdminTagEdit.vue') },

            // Phase 6 — sidebar widget CRUD. Same list + new/edit
            // pattern as the taxonomy pages above.
            { path: 'settings/widgets',       name: 'AdminWidgets',    component: () => import('../views/admin/AdminWidgets.vue') },
            { path: 'settings/widgets/new',   name: 'AdminWidgetNew',  component: () => import('../views/admin/AdminWidgetEdit.vue') },
            { path: 'settings/widgets/:id',   name: 'AdminWidgetEdit', component: () => import('../views/admin/AdminWidgetEdit.vue') },

            // Phase 8 — admin-editable social links. The public
            // topbar and footer read these via a view composer in
            // AppServiceProvider.
            { path: 'settings/social-links',       name: 'AdminSocialLinks',    component: () => import('../views/admin/AdminSocialLinks.vue') },
            { path: 'settings/social-links/new',   name: 'AdminSocialLinkNew',  component: () => import('../views/admin/AdminSocialLinkEdit.vue') },
            { path: 'settings/social-links/:id',   name: 'AdminSocialLinkEdit', component: () => import('../views/admin/AdminSocialLinkEdit.vue') },

            // Phase 9 — admin-editable pages (About, Contact, and
            // any future static page). The public /api/pages
            // endpoints serve the same data the admin writes here.
            { path: 'settings/pages',       name: 'AdminPages',    component: () => import('../views/admin/AdminPages.vue') },
            { path: 'settings/pages/new',   name: 'AdminPageNew',  component: () => import('../views/admin/AdminPageEdit.vue') },
            { path: 'settings/pages/:id',   name: 'AdminPageEdit', component: () => import('../views/admin/AdminPageEdit.vue') },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
