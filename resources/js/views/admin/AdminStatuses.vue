<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Statuses</h2>
            <router-link
                to="/admin/settings/statuses/new"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
            >
                New status
            </router-link>
        </div>

        <!--
          Search bar. Debounced 300ms so each keystroke doesn't
          fire a request. Resets to page 1.
        -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-4 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <label for="statuses-search" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input
                    id="statuses-search"
                    v-model="search"
                    type="search"
                    placeholder="Search by name, label, or slug…"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <button
                v-if="hasActiveFilters"
                type="button"
                class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                @click="clearFilters"
            >
                Clear
            </button>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>

        <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Loading…</p>

        <p v-if="!loading && !error && statuses.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            <template v-if="hasActiveFilters">No statuses match your search.</template>
            <template v-else>No statuses yet.</template>
        </p>

        <div v-if="!loading && statuses.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Color</th>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in statuses" :key="s.id" class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-block w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: s.color }"
                                ></span>
                                {{ s.label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ s.slug }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ s.color }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ s.order }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ s.description || '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <router-link
                                :to="`/admin/settings/statuses/${s.id}`"
                                class="text-blue-600 dark:text-blue-400 hover:underline"
                            >Edit</router-link>
                            <button
                                type="button"
                                class="text-red-600 dark:text-red-400 hover:underline"
                                @click="destroy(s)"
                            >Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="lastPage > 1" class="mt-6 flex justify-center gap-2">
            <button
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                :disabled="currentPage <= 1"
                @click="load(currentPage - 1)"
            >Previous</button>
            <span class="px-4 py-2 bg-blue-600 text-white rounded">{{ currentPage }} / {{ lastPage }}</span>
            <button
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
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
const statuses = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);

// Search state. Debounced so each keystroke doesn't fire a request.
// The watcher resets to page 1 since the new result set starts there.
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
        const { data } = await axios.get('/api/admin/statuses', { params: buildParams(page) });
        statuses.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load statuses.';
    } finally {
        loading.value = false;
    }
};

const destroy = async (s) => {
    if (! confirm(`Delete status "${s.label}"?`)) return;
    try {
        await axios.delete(`/api/admin/statuses/${s.id}`);
        await load(currentPage.value);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete status.';
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
