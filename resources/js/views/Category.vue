<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Category: {{ categoryName }}</h1>
                    <p class="text-gray-600 mt-2">Browse all posts in this category</p>
                </div>

                <p v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ error }}
                </p>

                <p v-if="loading" class="text-sm text-gray-500">Loading posts…</p>

                <p v-if="!loading && posts.length === 0 && !error" class="text-sm text-gray-500">
                    No published posts in this category yet.
                </p>

                <div v-if="!loading && posts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <PostCard v-for="post in posts" :key="post.id" :post="post" />
                </div>

                <nav v-if="lastPage > 1" class="mt-8 flex justify-center gap-2">
                    <button
                        class="px-4 py-2 bg-white border rounded hover:bg-gray-50 disabled:opacity-50"
                        :disabled="currentPage <= 1"
                        @click="changePage(currentPage - 1)"
                    >Previous</button>
                    <span class="px-4 py-2 bg-blue-600 text-white rounded">
                        {{ currentPage }} / {{ lastPage }}
                    </span>
                    <button
                        class="px-4 py-2 bg-white border rounded hover:bg-gray-50 disabled:opacity-50"
                        :disabled="currentPage >= lastPage"
                        @click="changePage(currentPage + 1)"
                    >Next</button>
                </nav>
            </div>

            <div class="lg:col-span-1">
                <Sidebar />
            </div>
        </div>

        <BackToTop />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import BackToTop from '../components/BackToTop.vue';
import PostCard from '../components/PostCard.vue';
import Sidebar from '../components/Sidebar.vue';
import useApi from '../composables/useApi';

const route = useRoute();
const api = useApi();

const loading = ref(true);
const error = ref(null);
const posts = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const notFound = ref(false);

const categoryName = computed(() => {
    const slug = route.params.slug ?? '';
    if (!slug) return '';
    return slug
        .toString()
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
});

const loadCategory = async (slug, page = 1) => {
    loading.value = true;
    error.value = null;
    notFound.value = false;
    try {
        const data = await api.listPostsByCategory(slug, page, 9);
        posts.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        if (e?.response?.status === 404) {
            notFound.value = true;
            error.value = 'Category not found.';
        } else {
            error.value = e?.response?.data?.message ?? e?.message ?? 'Failed to load posts.';
        }
        posts.value = [];
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    const slug = route.params.slug;
    if (!slug) return;
    loadCategory(slug, page);
};

watch(
    () => route.params.slug,
    (slug) => {
        if (slug) loadCategory(slug, 1);
    },
);

onMounted(() => {
    if (route.params.slug) loadCategory(route.params.slug, 1);
});
</script>
