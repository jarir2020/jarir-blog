<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-8">
            <router-link
                v-if="page?.parent_slug"
                :to="{ name: 'About' }"
                class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
            >← Back to {{ parentTitle }}</router-link>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2 mb-4">
                {{ page?.title || 'Page' }}
            </h1>

            <p v-if="error" class="mb-6 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">
                {{ error }}
            </p>

            <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Loading…</p>

            <article
                v-if="page"
                class="prose prose-lg dark:prose-invert max-w-none"
                v-html="page.body_html"
            ></article>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import useApi from '../composables/useApi';

const route = useRoute();
const api = useApi();

const loading = ref(true);
const error = ref(null);
const page = ref(null);

// "Back to About" link shows the parent page's title when
// available. Falls back to "About" for the index case.
const parentTitle = computed(() => {
    if (!page.value?.parent_slug) return 'About';
    return page.value.parent_slug === 'about' ? 'About' : page.value.parent_slug;
});

const loadPage = async (slug) => {
    loading.value = true;
    error.value = null;
    page.value = null;
    try {
        const data = await api.getPage(slug);
        page.value = data.page;
    } catch (e) {
        if (e?.response?.status === 404) {
            error.value = 'Page not found.';
        } else {
            error.value = e?.response?.data?.message ?? e?.message ?? 'Failed to load page.';
        }
    } finally {
        loading.value = false;
    }
};

watch(
    () => route.params.slug,
    (slug) => {
        if (slug) loadPage(slug);
    },
);

onMounted(() => {
    if (route.params.slug) loadPage(route.params.slug);
});
</script>
