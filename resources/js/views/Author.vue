<template>
    <AppHeader />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
            {{ error }}
        </p>

        <div v-if="author" class="mb-8 bg-white rounded-lg shadow-sm p-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ author.name }}</h1>
            <p class="text-sm text-gray-500 mt-1">@{{ author.handle }}</p>
            <p class="text-gray-600 mt-3">
                {{ author.posts_count }} published {{ author.posts_count === 1 ? 'post' : 'posts' }}
            </p>
        </div>

        <p v-if="loading" class="text-sm text-gray-500">Loading posts…</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <PostCard
                v-for="post in posts"
                :key="post.id"
                :post="post"
                @open="goToPost"
            />
        </div>

        <nav v-if="lastPage > 1" class="mt-12 flex justify-center gap-2">
            <button
                class="px-4 py-2 bg-white border rounded hover:bg-gray-50 disabled:opacity-50"
                :disabled="currentPage <= 1"
                @click="changePage(currentPage - 1)"
            >
                Previous
            </button>
            <span class="px-4 py-2 bg-blue-600 text-white rounded">
                {{ currentPage }} / {{ lastPage }}
            </span>
            <button
                class="px-4 py-2 bg-white border rounded hover:bg-gray-50 disabled:opacity-50"
                :disabled="currentPage >= lastPage"
                @click="changePage(currentPage + 1)"
            >
                Next
            </button>
        </nav>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppHeader from '../components/AppHeader.vue';
import PostCard from '../components/PostCard.vue';
import useApi from '../composables/useApi';

const route = useRoute();
const router = useRouter();
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

const goToPost = (post) => {
    if (!post?.slug) return;
    router.push({ name: 'BlogPost', params: { slug: post.slug } });
};

watch(
    () => route.params.username,
    (handle) => {
        currentPage.value = 1;
        if (handle) loadAuthor(handle);
    },
    { immediate: false }
);

onMounted(() => {
    if (route.params.username) loadAuthor(route.params.username);
});
</script>
