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

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>

        <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

        <table v-if="!loading" class="min-w-full bg-white shadow-sm rounded-lg overflow-hidden">
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
                        <span :class="statusClass(post.status)" class="px-2 py-0.5 rounded-full text-xs">
                            {{ post.status }}
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
import { onMounted, ref } from 'vue';
import { formatDate } from '../../composables/format';

const loading = ref(true);
const error = ref(null);
const posts = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);

const load = async (page = 1) => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/posts', { params: { page, per_page: 20 } });
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

const statusClass = (status) => ({
    'bg-green-100 text-green-800': status === 'published',
    'bg-yellow-100 text-yellow-800': status === 'draft',
    'bg-gray-100 text-gray-800': status === 'archived',
});

onMounted(() => load(1));
</script>
