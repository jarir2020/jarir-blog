<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Search</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        <span v-if="query">Results for &ldquo;<strong>{{ query }}</strong>&rdquo;:</span>
                        <span v-else>Type a query to search published posts.</span>
                    </p>
                </div>

                <form class="mb-6 flex gap-2" @submit.prevent="runSearch">
                    <input
                        v-model="inputValue"
                        type="search"
                        placeholder="Search posts…"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >Search</button>
                </form>

                <p v-if="error" class="mb-6 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Searching…</p>
                <p v-if="!loading && results.length === 0 && query" class="text-sm text-gray-500 dark:text-gray-400">
                    No posts matched &ldquo;{{ query }}&rdquo;.
                </p>

                <div v-if="!loading && results.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <PostCard v-for="post in results" :key="post.id" :post="post" />
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
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BackToTop from '../components/BackToTop.vue';
import PostCard from '../components/PostCard.vue';
import Sidebar from '../components/Sidebar.vue';
import useApi from '../composables/useApi';

const route = useRoute();
const router = useRouter();
const api = useApi();

const inputValue = ref(route.query.q ?? '');
const loading = ref(false);
const error = ref(null);
const results = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const query = computed(() => (route.query.q ?? '').toString());

const runSearch = async (page = 1) => {
    const q = inputValue.value.trim();
    if (q === '') {
        results.value = [];
        currentPage.value = 1;
        lastPage.value = 1;
        total.value = 0;
        return;
    }
    loading.value = true;
    error.value = null;
    try {
        const data = await api.searchPosts(q, page, 12);
        results.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
        total.value = data.total ?? 0;
    } catch (e) {
        error.value = e?.response?.data?.message ?? e?.message ?? 'Search failed.';
        results.value = [];
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    router.replace({ query: { ...route.query, q: inputValue.value, page } });
    runSearch(page);
};

watch(
    () => route.query,
    (q) => {
        inputValue.value = q.q ?? '';
        const page = parseInt(q.page ?? '1', 10) || 1;
        if (q.q) runSearch(page);
    },
    { deep: true },
);

onMounted(() => {
    if (query.value) runSearch(parseInt(route.query.page ?? '1', 10) || 1);
});
</script>
