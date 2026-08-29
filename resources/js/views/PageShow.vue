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
                    {{ page?.title || slug }}
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
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import useApi from '../composables/useApi';

const route = useRoute();
const api = useApi();

const loading = ref(true);
const error = ref(null);
const page = ref(null);

// Vue Router 4's `/:slug(.*)*` puts the matched path into
// `route.params.slug` as a string (when defined). The /about
// route is listed explicitly and passes a `slug` prop. Fall
// back to the prop if the param is missing.
const slugProp = defineProps({ slug: { type: String, default: '' } });

const slug = computed(() => {
    if (slugProp.slug) return slugProp.slug;
    const raw = route.params.slug ?? '';
    if (Array.isArray(raw)) return raw.filter(Boolean).join('/');
    return String(raw).replace(/^\/+/, '');
});

const loadPage = async (s) => {
    if (!s) return;
    loading.value = true;
    error.value = null;
    page.value = null;
    try {
        const data = await api.getPage(s);
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
    (s) => loadPage(typeof s === 'string' ? s : (s ?? []).filter(Boolean).join('/')),
);

onMounted(() => loadPage(slug.value));
</script>
