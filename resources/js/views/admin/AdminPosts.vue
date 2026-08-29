<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Posts</h2>
            <router-link
                to="/admin/posts/new"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
            >
                New post
            </router-link>
        </div>

        <!--
          Search + filter bar. We debounce the search input so each
          keystroke doesn't fire a request, but the status/category
          selects trigger an immediate reload. Clearing any filter
          also reloads (and resets to page 1).
        -->
        <div class="bg-white shadow-sm rounded-lg p-4 mb-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[220px]">
                <label for="posts-search" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input
                    id="posts-search"
                    v-model="search"
                    type="search"
                    placeholder="Search title, excerpt, or content…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <div class="w-40">
                <label for="posts-status" class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select
                    id="posts-status"
                    v-model="statusId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">All statuses</option>
                    <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.label }}</option>
                </select>
            </div>
            <div class="w-48">
                <label for="posts-category" class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                <select
                    id="posts-category"
                    v-model="categoryId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">All categories</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <button
                v-if="hasActiveFilters"
                type="button"
                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
                @click="clearFilters"
            >
                Clear
            </button>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>

        <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

        <p v-if="!loading && !error && posts.length === 0" class="text-sm text-gray-500">
            No posts match your filters.
        </p>

        <table v-if="!loading && posts.length > 0" class="min-w-full bg-white shadow-sm rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Featured</th>
                    <th class="px-4 py-3">Views</th>
                    <th class="px-4 py-3">Updated</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="post in posts" :key="post.id" class="border-t border-gray-200">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ post.title }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span
                            v-if="post.status"
                            :style="statusStyle(post.status)"
                            class="px-2 py-0.5 rounded-full text-xs"
                        >
                            {{ post.status.label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ post.is_featured ? '⭐' : '' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ post.views }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(post.updated_at) }}</td>
                    <td class="px-4 py-3 text-right text-sm space-x-2">
                        <router-link
                            :to="`/admin/posts/${post.id}`"
                            class="text-blue-600 hover:underline"
                        >Edit</router-link>
                        <button
                            type="button"
                            class="text-red-600 hover:underline"
                            @click="destroy(post)"
                        >Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <nav v-if="lastPage > 1" class="mt-6 flex justify-center gap-2">
            <button
                class="px-4 py-2 bg-white border rounded hover:bg-gray-50 disabled:opacity-50"
                :disabled="currentPage <= 1"
                @click="load(currentPage - 1)"
            >Previous</button>
            <span class="px-4 py-2 bg-blue-600 text-white rounded">{{ currentPage }} / {{ lastPage }}</span>
            <button
                class="px-4 py-2 bg-white border rounded hover:bg-gray-50 disabled:opacity-50"
                :disabled="currentPage >= lastPage"
                @click="load(currentPage + 1)"
            >Next</button>
        </nav>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { formatDate } from '../../composables/format';

const loading = ref(true);
const error = ref(null);
const posts = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);

// Filter state. Bound to the form controls above. We watch each of
// these (debounced for `search`) and re-run the query against page 1.
const search = ref('');
const statusId = ref('');
const categoryId = ref('');
const categories = ref([]);
const statuses = ref([]);

const hasActiveFilters = computed(
    () => search.value !== '' || statusId.value !== '' || categoryId.value !== '',
);

const buildParams = (page = 1) => {
    const params = { page, per_page: 20 };
    if (search.value.trim() !== '') params.q = search.value.trim();
    if (statusId.value !== '') params.status_id = statusId.value;
    if (categoryId.value !== '') params.category_id = categoryId.value;
    return params;
};

const load = async (page = 1) => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/posts', { params: buildParams(page) });
        posts.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load posts.';
    } finally {
        loading.value = false;
    }
};

const destroy = async (post) => {
    if (! confirm(`Delete "${post.title}"?`)) return;
    try {
        await axios.delete(`/api/admin/posts/${post.id}`);
        await load(currentPage.value);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete post.';
    }
};

// Render the status pill using the admin-configured color. Falls back
// to a neutral gray if a status somehow has no color set.
const statusStyle = (status) => {
    const color = status?.color || '#6b7280';
    return {
        backgroundColor: `${color}22`, // 13% alpha for the bg
        color,
        border: `1px solid ${color}55`,
    };
};

const clearFilters = () => {
    search.value = '';
    statusId.value = '';
    categoryId.value = '';
};

// Debounce the search input so each keystroke doesn't fire a request.
// The dropdowns trigger an immediate reload — there's only one event
// per change, no debounce needed.
let searchTimer = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => load(1), 300);
});
watch(statusId, () => load(1));
watch(categoryId, () => load(1));

const loadCategories = async () => {
    try {
        const { data } = await axios.get('/api/categories', { headers: { Accept: 'application/json' } });
        categories.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch {
        // Non-fatal — the filter just shows "All categories" only.
        categories.value = [];
    }
};

const loadStatuses = async () => {
    try {
        const { data } = await axios.get('/api/admin/statuses', { params: { per_page: 100 } });
        statuses.value = (data.data ?? []).sort((a, b) => a.order - b.order);
    } catch {
        // Non-fatal — filter just shows "All statuses" only.
        statuses.value = [];
    }
};

onMounted(() => {
    loadCategories();
    loadStatuses();
    load(1);
});
</script>
