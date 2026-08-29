<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Tags</h2>
            <router-link
                to="/admin/settings/tags/new"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
            >
                New tag
            </router-link>
        </div>

        <!-- Search bar. Debounced 300ms; resets to page 1. -->
        <div class="bg-white shadow-sm rounded-lg p-4 mb-4 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <label for="tags-search" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input
                    id="tags-search"
                    v-model="search"
                    type="search"
                    placeholder="Search by name or slug…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
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

        <p v-if="!loading && !error && tags.length === 0" class="text-sm text-gray-500">
            <template v-if="hasActiveFilters">No tags match your search.</template>
            <template v-else>No tags yet.</template>
        </p>

        <div v-if="!loading && tags.length > 0" class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Color</th>
                        <th class="px-4 py-3">Posts</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in tags" :key="t.id" class="border-t border-gray-200">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-block w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: t.color }"
                                ></span>
                                {{ t.name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ t.slug }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ t.color }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ t.posts_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <router-link
                                :to="`/admin/settings/tags/${t.id}`"
                                class="text-blue-600 hover:underline"
                            >Edit</router-link>
                            <button
                                type="button"
                                class="text-red-600 hover:underline"
                                @click="destroy(t)"
                            >Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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

const loading = ref(true);
const error = ref(null);
const tags = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);

const search = ref('');

const hasActiveFilters = computed(() => search.value.trim() !== '');

const buildParams = (page = 1) => {
    const params = { page, per_page: 50 };
    if (search.value.trim() !== '') params.q = search.value.trim();
    return params;
};

const load = async (page = 1) => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/tags', { params: buildParams(page) });
        tags.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load tags.';
    } finally {
        loading.value = false;
    }
};

const destroy = async (t) => {
    if (! confirm(`Delete tag "${t.name}"?`)) return;
    try {
        await axios.delete(`/api/admin/tags/${t.id}`);
        await load(currentPage.value);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete tag.';
    }
};

const clearFilters = () => {
    search.value = '';
};

let searchTimer = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => load(1), 300);
});

onMounted(() => load(1));
</script>
