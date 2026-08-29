<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Sidebar Widgets</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Drag-free list — use the up / down buttons to reorder, or just edit a widget's <code class="font-mono">order</code> field directly.
                </p>
            </div>
            <router-link
                to="/admin/settings/widgets/new"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
            >
                New widget
            </router-link>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

        <p v-if="!loading && widgets.length === 0" class="text-sm text-gray-500">
            No widgets yet. Create one to populate the public sidebar.
        </p>

        <div v-if="!loading && widgets.length > 0" class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3 w-12">Order</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Enabled</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="w in widgets" :key="w.id" class="border-t border-gray-200">
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="px-1 text-gray-400 hover:text-gray-700"
                                    :disabled="w === widgets[0]"
                                    @click="move(w, -1)"
                                    title="Move up"
                                >▲</button>
                                <button
                                    type="button"
                                    class="px-1 text-gray-400 hover:text-gray-700"
                                    :disabled="w === widgets[widgets.length - 1]"
                                    @click="move(w, 1)"
                                    title="Move down"
                                >▼</button>
                                <span class="ml-1">{{ w.order }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ w.name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ w.type }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ w.position }}</td>
                        <td class="px-4 py-3 text-sm">
                            <button
                                type="button"
                                @click="toggle(w)"
                                :class="[
                                    'px-2 py-0.5 rounded-full text-xs',
                                    w.enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600',
                                ]"
                            >{{ w.enabled ? 'On' : 'Off' }}</button>
                        </td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <router-link
                                :to="`/admin/settings/widgets/${w.id}`"
                                class="text-blue-600 hover:underline"
                            >Edit</router-link>
                            <button
                                type="button"
                                class="text-red-600 hover:underline"
                                @click="destroy(w)"
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
const widgets = ref([]);

const load = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/widgets', { params: { per_page: 100 } });
        widgets.value = (data.data ?? []).sort((a, b) => a.order - b.order || a.id - b.id);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load widgets.';
    } finally {
        loading.value = false;
    }
};

const toggle = async (w) => {
    try {
        await axios.put(`/api/admin/widgets/${w.id}`, { enabled: !w.enabled });
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to toggle widget.';
    }
};

// Swap the order with the neighbour. We compute the midpoint of the
// two existing `order` values so the change is always numeric — much
// simpler than renumbering every row.
const move = async (w, direction) => {
    const idx = widgets.value.findIndex((x) => x.id === w.id);
    const swapIdx = idx + direction;
    if (swapIdx < 0 || swapIdx >= widgets.value.length) return;

    const other = widgets.value[swapIdx];
    try {
        // The midpoint of (a, b) keeps the relative order intact and
        // can be applied to both rows in a single update cycle.
        const mid = Math.floor((w.order + other.order) / 2);
        await Promise.all([
            axios.put(`/api/admin/widgets/${w.id}`, { order: w.order < other.order ? mid - 1 : mid + 1 }),
            axios.put(`/api/admin/widgets/${other.id}`, { order: mid }),
        ]);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to reorder.';
    }
};

const destroy = async (w) => {
    if (! confirm(`Delete widget "${w.name}"?`)) return;
    try {
        await axios.delete(`/api/admin/widgets/${w.id}`);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete widget.';
    }
};

onMounted(load);
</script>
