<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Social Links</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Brand profile links shown in the public site's top utility bar and footer.
                </p>
            </div>
            <router-link
                to="/admin/settings/social-links/new"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
            >
                New social link
            </router-link>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
        <p v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">Loading…</p>

        <p v-if="!loading && !error && links.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            No social links yet.
        </p>

        <div v-if="!loading && links.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr class="text-left text-xs text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        <th class="px-4 py-3 w-12">Order</th>
                        <th class="px-4 py-3">Label</th>
                        <th class="px-4 py-3">Platform</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3">Enabled</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in links" :key="l.id" class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="px-1 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    :disabled="l === links[0]"
                                    @click="move(l, -1)"
                                    title="Move up"
                                >▲</button>
                                <button
                                    type="button"
                                    class="px-1 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    :disabled="l === links[links.length - 1]"
                                    @click="move(l, 1)"
                                    title="Move down"
                                >▼</button>
                                <span class="ml-1">{{ l.order }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ l.label }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ l.platform }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                            <a :href="l.url" target="_blank" rel="noopener" class="hover:underline">{{ l.url }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <button
                                type="button"
                                @click="toggle(l)"
                                :class="[
                                    'px-2 py-0.5 rounded-full text-xs',
                                    l.enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                ]"
                            >{{ l.enabled ? 'On' : 'Off' }}</button>
                        </td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <router-link
                                :to="`/admin/settings/social-links/${l.id}`"
                                class="text-blue-600 dark:text-blue-400 hover:underline"
                            >Edit</router-link>
                            <button
                                type="button"
                                class="text-red-600 dark:text-red-400 hover:underline"
                                @click="destroy(l)"
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
const links = ref([]);

const load = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/social-links', { params: { per_page: 100 } });
        links.value = (data.data ?? []).sort((a, b) => a.order - b.order || a.id - b.id);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load social links.';
    } finally {
        loading.value = false;
    }
};

const toggle = async (l) => {
    try {
        await axios.put(`/api/admin/social-links/${l.id}`, { enabled: !l.enabled });
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to toggle link.';
    }
};

// Swap the order with the neighbour using the midpoint trick.
const move = async (l, direction) => {
    const idx = links.value.findIndex((x) => x.id === l.id);
    const swapIdx = idx + direction;
    if (swapIdx < 0 || swapIdx >= links.value.length) return;

    const other = links.value[swapIdx];
    try {
        const mid = Math.floor((l.order + other.order) / 2);
        await Promise.all([
            axios.put(`/api/admin/social-links/${l.id}`, { order: l.order < other.order ? mid - 1 : mid + 1 }),
            axios.put(`/api/admin/social-links/${other.id}`, { order: mid }),
        ]);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to reorder.';
    }
};

const destroy = async (l) => {
    if (! confirm(`Delete "${l.label}"?`)) return;
    try {
        await axios.delete(`/api/admin/social-links/${l.id}`);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete link.';
    }
};

onMounted(load);
</script>
