<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <p v-if="error" class="mb-6 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">
                    {{ error }}
                </p>

                <div v-if="author" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ author.name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">@{{ author.handle }}</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-3">
                        {{ author.posts_count }} published {{ author.posts_count === 1 ? 'post' : 'posts' }}
                    </p>
                </div>

                <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Loading posts…</p>

                <div v-if="!loading && posts.length === 0 && !error" class="text-sm text-gray-500 dark:text-gray-400">
                    No published posts by this author yet.
                </div>

                <div v-if="!loading && posts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <PostCard v-for="post in posts" :key="post.id" :post="post" />
                </div>

                <nav v-if="lastPage > 1" class="mt-8 flex justify-center gap-2">
                    <button
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                        :disabled="currentPage <= 1"
                        @click="changePage(currentPage - 1)"
                    >Previous</button>
                    <span class="px-4 py-2 bg-blue-600 text-white rounded">
                        {{ currentPage }} / {{ lastPage }}
                    </span>
                    <button
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
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
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import BackToTop from '../components/BackToTop.vue';
import PostCard from '../components/PostCard.vue';
import Sidebar from '../components/Sidebar.vue';
import useApi from '../composables/useApi';

const route = useRoute();
const api = useApi();

const loading = ref(true);
const error = ref(null);
const author = ref(null);
const posts = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);

const loadAuthor = async (handle) => {
    loading.value = true;
    error.value = null;
    author.value = null;
    posts.value = [];
    try {
        const data = await api.getAuthor(handle);
        author.value = data.author ?? null;
    } catch (e) {
        error.value = e?.response?.status === 404
            ? 'Author not found.'
            : (e?.response?.data?.message ?? e?.message ?? 'Failed to load author.');
        return;
    }
    try {
        const data = await api.getAuthorPosts(handle, currentPage.value, 9);
        posts.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        error.value = e?.response?.data?.message ?? e?.message ?? 'Failed to load posts.';
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    currentPage.value = page;
    loadAuthor(route.params.username);
};

watch(
    () => route.params.username,
    (handle) => {
        currentPage.value = 1;
        if (handle) loadAuthor(handle);
    },
);

onMounted(() => {
    if (route.params.username) loadAuthor(route.params.username);
});
</script>
