<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <article class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <img
                v-if="page?.hero_image"
                :src="page.hero_image"
                :alt="page.title"
                class="w-full h-64 md:h-80 object-cover"
                @error="(e) => (e.target.style.display = 'none')"
            />

            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    {{ page?.title || 'About Us' }}
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
        </article>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import useApi from '../composables/useApi';

const api = useApi();

const loading = ref(true);
const error = ref(null);
const page = ref(null);

onMounted(async () => {
    try {
        const data = await api.getPage('about');
        page.value = data.page;
    } catch (e) {
        if (e?.response?.status === 404) {
            error.value = 'The About page is not configured yet.';
        } else {
            error.value = e?.response?.data?.message ?? e?.message ?? 'Failed to load page.';
        }
    } finally {
        loading.value = false;
    }
});
</script>
