<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                {{ aboutPage?.title || 'About Us' }}
            </h1>
            <p v-if="aboutPage?.excerpt" class="text-gray-600 dark:text-gray-400 mb-8">{{ aboutPage.excerpt }}</p>
            <p v-if="aboutPage?.body_html" class="text-gray-700 dark:text-gray-300 leading-relaxed mb-8" v-html="aboutPage.body_html"></p>

            <hr class="border-gray-200 dark:border-gray-700 mb-8" />

            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Pages</h2>

            <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Loading…</p>

            <p v-if="!loading && subPages.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                No sub-pages yet.
            </p>

            <div v-if="!loading && subPages.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <router-link
                    v-for="p in subPages"
                    :key="p.id"
                    :to="{ name: 'AboutPage', params: { slug: p.slug } }"
                    class="block bg-gray-50 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-lg p-5 transition-colors"
                >
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ p.title }}</h3>
                    <p v-if="p.excerpt" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ p.excerpt }}</p>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import useApi from '../composables/useApi';

const api = useApi();

const loading = ref(true);
const aboutPage = ref(null);
const subPages = ref([]);

onMounted(async () => {
    try {
        // Fetch the about index page (slug "about") and its children
        // in parallel. The two requests are independent so we don't
        // need to wait for one to finish before starting the other.
        const [about, subs] = await Promise.all([
            api.getPage('about').catch(() => null),
            api.getPages('about').catch(() => ({ data: [] })),
        ]);
        aboutPage.value = about?.page ?? null;
        subPages.value = subs?.data ?? [];
    } finally {
        loading.value = false;
    }
});
</script>
