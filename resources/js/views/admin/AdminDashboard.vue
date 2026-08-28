<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500">Posts</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.posts ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500">Comments (pending)</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.pending_comments ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500">Subscribers</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.subscribers ?? '—' }}</p>
            </div>
        </div>

        <p v-if="error" class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const stats = ref({});
const error = ref(null);

const load = async () => {
    try {
        const [{ data: posts }, { data: comments }, { data: subs }] = await Promise.all([
            axios.get('/api/admin/posts', { params: { per_page: 1 } }),
            axios.get('/api/admin/comments', { params: { per_page: 1, filter: 'pending' } }),
            axios.get('/api/admin/subscribers', { params: { per_page: 1 } }).catch(() => ({ data: { total: null } })),
        ]);
        stats.value = {
            posts: posts.total,
            pending_comments: comments.total,
            subscribers: subs?.total ?? null,
        };
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load dashboard stats.';
    }
};

onMounted(load);
</script>
