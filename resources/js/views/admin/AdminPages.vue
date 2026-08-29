<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Pages</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Static pages like About, Contact, and any sub-page. Bodies are written in Markdown.
                </p>
            </div>
            <router-link
                to="/admin/settings/pages/new"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
            >
                New page
            </router-link>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
        <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Loading…</p>

        <p v-if="!loading && !error && pages.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            No pages yet.
        </p>

        <div v-if="!loading && pages.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr class="text-left text-xs text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Parent</th>
                        <th class="px-4 py-3 w-16">Order</th>
                        <th class="px-4 py-3">Enabled</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in pages" :key="p.id" class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ p.slug }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ p.title }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">
                            {{ p.parent_slug || '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ p.order }}</td>
                        <td class="px-4 py-3 text-sm">
                            <button
                                type="button"
                                @click="toggle(p)"
                                :class="[
                                    'px-2 py-0.5 rounded-full text-xs',
                                    p.enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                ]"
                            >{{ p.enabled ? 'On' : 'Off' }}</button>
                        </td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <router-link
                                :to="`/admin/settings/pages/${p.id}`"
                                class="text-blue-600 dark:text-blue-400 hover:underline"
                            >Edit</router-link>
                            <button
                                type="button"
                                class="text-red-600 dark:text-red-400 hover:underline"
                                @click="destroy(p)"
                            >Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const loading = ref(true);
const error = ref(null);
const pages = ref([]);

const load = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/pages', { params: { per_page: 100 } });
        // The server orders by (parent_slug, order, id); keep that
        // ordering on the client so the table matches the API
        // response shape and so deletions don't reorder rows.
        pages.value = (data.data ?? []).sort((a, b) => {
            if ((a.parent_slug || '') < (b.parent_slug || '')) return -1;
            if ((a.parent_slug || '') > (b.parent_slug || '')) return 1;
            return (a.order - b.order) || (a.id - b.id);
        });
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load pages.';
    } finally {
        loading.value = false;
    }
};

const toggle = async (p) => {
    try {
        await axios.put(`/api/admin/pages/${p.id}`, { enabled: !p.enabled });
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to toggle page.';
    }
};

const destroy = async (p) => {
    if (! confirm(`Delete "${p.title}"?`)) return;
    try {
        await axios.delete(`/api/admin/pages/${p.id}`);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete page.';
    }
};

onMounted(load);
</script>
