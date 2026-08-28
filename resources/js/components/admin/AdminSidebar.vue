<template>
    <aside class="w-60 shrink-0 bg-gray-900 text-gray-300 min-h-screen flex flex-col">
        <div class="px-5 py-5 border-b border-gray-800">
            <router-link to="/admin" class="block text-lg font-bold text-white">
                Jarir Blog
            </router-link>
            <p class="text-xs text-gray-500 uppercase tracking-wider mt-0.5">Admin</p>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-0.5">
            <router-link
                v-for="item in items"
                :key="item.label"
                :to="item.to"
                :exact="item.exact"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-gray-800 hover:text-white"
                active-class="bg-gray-800 text-white"
            >
                <component :is="item.icon" class="w-5 h-5 shrink-0" />
                <span>{{ item.label }}</span>
                <span
                    v-if="item.badge && item.badge > 0"
                    class="ml-auto px-2 py-0.5 text-xs rounded-full bg-red-500 text-white"
                >
                    {{ item.badge }}
                </span>
            </router-link>
        </nav>

        <div class="px-3 py-4 border-t border-gray-800 text-sm">
            <a href="/" class="block px-3 py-2 text-gray-400 hover:text-white">
                ← Back to blog
            </a>
        </div>
    </aside>
</template>

<script setup>
import { computed, h } from 'vue';

const props = defineProps({
    pendingComments: { type: Number, default: 0 },
});

const svg = (path) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            viewBox: '0 0 20 20',
            fill: 'currentColor',
            'aria-hidden': 'true',
        },
        [h('path', { d: path })]
    );

// Heroicons (mini) — paths are static strings I control, so rendering
// them as render functions is safe and ESLint-friendly (no v-html).
const baseItems = [
    {
        to: '/admin',
        label: 'Dashboard',
        exact: true,
        icon: () => svg('M3 4a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5V4Zm0 6a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5v-3Zm0 6a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5v-3ZM9 4a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5H11a.5.5 0 0 1-.5-.5V4Zm0 6a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5H11a.5.5 0 0 1-.5-.5v-3Zm0 6a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5H11a.5.5 0 0 1-.5-.5v-3Zm6-12a2 2 0 0 0-2-2h-2.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5H15a.5.5 0 0 0 .5-.5V4Zm0 6a2 2 0 0 0-2-2h-2.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5H15a.5.5 0 0 0 .5-.5v-3Zm0 6a2 2 0 0 0-2-2h-2.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5H15a.5.5 0 0 0 .5-.5v-3Z'),
    },
    {
        to: '/admin/posts',
        label: 'Posts',
        icon: () => svg('M3 3.5A1.5 1.5 0 0 1 4.5 2h6.879a1.5 1.5 0 0 1 1.06.44l4.122 4.12A1.5 1.5 0 0 1 17 7.622V16.5A1.5 1.5 0 0 1 15.5 18h-11A1.5 1.5 0 0 1 3 16.5v-13ZM5 7v1.5h6V7H5Zm6 3H5v1.5h6V10Zm-6 3h4.5v-1.5H5V13Z'),
    },
    {
        to: '/admin/posts/new',
        label: 'New post',
        icon: () => svg('M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z'),
    },
    {
        to: '/admin/comments',
        label: 'Comments',
        icon: () => svg('M3.5 4A1.5 1.5 0 0 1 5 2.5h10A1.5 1.5 0 0 1 16.5 4v8a1.5 1.5 0 0 1-1.5 1.5H8.621l-3.06 2.561A.5.5 0 0 1 4.5 14V4Z'),
    },
];

const items = computed(() =>
    baseItems.map((item) => {
        if (item.to === '/admin/comments' && props.pendingComments > 0) {
            return { ...item, badge: props.pendingComments };
        }
        return item;
    })
);
</script>
