<template>
    <AppHeader />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="bg-blue-600 rounded-lg shadow-lg p-8 mb-12 text-white">
            <h1 class="text-4xl font-bold mb-4">Welcome to Jarir Blog</h1>
            <p class="text-lg opacity-90">
                Discover insightful articles, news, and stories from around the world.
            </p>
        </div>

        <p v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
            {{ error }}
        </p>

        <p v-if="loading" class="mb-6 text-sm text-gray-500">Loading posts…</p>

        <!-- Featured Posts -->
        <section v-if="featuredPosts.length > 0" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <PostCard
                    v-for="post in featuredPosts"
                    :key="post.id"
                    :post="post"
                    @open="goToPost"
                />
            </div>
        </section>

        <!-- Latest Posts -->
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Latest Posts</h2>
            <div v-if="!loading && latestPosts.length === 0" class="text-sm text-gray-500">
                No published posts yet. Add some via the database to see them here.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <PostCard
                    v-for="post in latestPosts"
                    :key="post.id"
                    :post="post"
                    @open="goToPost"
                />
            </div>
            <nav v-if="lastPage > 1" class="mt-8 flex justify-center gap-2">
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
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppHeader from '../components/AppHeader.vue';
import PostCard from '../components/PostCard.vue';
import useApi from '../composables/useApi';

const router = useRouter();
const api = useApi();

const loading = ref(true);
const error = ref(null);
const allPosts = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);

const loadPosts = async (page = 1) => {
    loading.value = true;
    error.value = null;
    try {
        const data = await api.listPosts(page, 12);
        allPosts.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        error.value = e?.response?.data?.message ?? e?.message ?? 'Failed to load posts.';
        allPosts.value = [];
    } finally {
        loading.value = false;
    }
};

const featuredPosts = computed(() =>
    allPosts.value.filter((post) => post.is_featured).slice(0, 3)
);

// "Latest" excludes the featured subset, so the two sections don't repeat.
const latestPosts = computed(() =>
    allPosts.value.filter((post) => !post.is_featured)
);

const changePage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    loadPosts(page);
};

const goToPost = (post) => {
    if (!post?.slug) return;
    router.push({ name: 'BlogPost', params: { slug: post.slug } });
};

onMounted(() => loadPosts(1));
</script>
