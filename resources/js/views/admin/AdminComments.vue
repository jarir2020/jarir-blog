<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Comments</h2>
            <div class="flex gap-1 bg-gray-100 p-1 rounded-md text-sm">
                <button
                    v-for="option in filters"
                    :key="option.value"
                    type="button"
                    :class="filter === option.value ? 'bg-white shadow' : ''"
                    class="px-3 py-1.5 rounded-md"
                    @click="setFilter(option.value)"
                >{{ option.label }}</button>
            </div>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="text-sm text-gray-500">Loading…</p>

        <ul v-if="!loading" class="space-y-3">
            <li
                v-for="comment in comments"
                :key="comment.id"
                class="bg-white rounded-lg shadow-sm p-4"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <strong class="text-gray-900">{{ comment.name }}</strong>
                            <span>&lt;{{ comment.email }}&gt;</span>
                            <span>·</span>
                            <span>{{ formatDate(comment.created_at) }}</span>
                            <span
                                v-if="!comment.approved"
                                class="ml-2 px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800"
                            >Pending</span>
                            <span
                                v-else
                                class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800"
                            >Approved</span>
                            <span v-if="comment.post" class="ml-2 text-xs text-gray-500">
                                on
                                <router-link
                                    :to="{ name: 'BlogPost', params: { slug: comment.post.slug } }"
                                    class="text-blue-600 hover:underline"
                                >{{ comment.post.title }}</router-link>
                            </span>
                        </div>
                        <p class="mt-2 text-gray-700 whitespace-pre-line">{{ comment.body }}</p>
                    </div>
                    <div class="flex flex-col gap-2 text-sm">
                        <button
                            v-if="!comment.approved"
                            type="button"
                            class="px-3 py-1.5 bg-green-600 text-white rounded-md hover:bg-green-700"
                            @click="moderate(comment, 'approve')"
                        >Approve</button>
                        <button
                            v-else
                            type="button"
                            class="px-3 py-1.5 bg-yellow-500 text-white rounded-md hover:bg-yellow-600"
                            @click="moderate(comment, 'reject')"
                        >Unapprove</button>
                        <button
                            type="button"
                            class="px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700"
                            @click="destroy(comment)"
                        >Delete</button>
                    </div>
                </div>
            </li>
        </ul>

        <p v-if="!loading && comments.length === 0" class="text-sm text-gray-500">
            No comments.
        </p>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';
import { formatDate } from '../../composables/format';

const loading = ref(true);
const error = ref(null);
const comments = ref([]);
const filter = ref('all');
const currentPage = ref(1);
const lastPage = ref(1);

const filters = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
];

const load = async (page = 1) => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/admin/comments', {
            params: { page, per_page: 25, filter: filter.value },
        });
        comments.value = data.data ?? [];
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load comments.';
    } finally {
        loading.value = false;
    }
};

const setFilter = (value) => {
    filter.value = value;
    load(1);
};

const moderate = async (comment, action) => {
    try {
        await axios.post(`/api/admin/comments/${comment.id}/${action}`);
        await load(currentPage.value);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to moderate comment.';
    }
};

const destroy = async (comment) => {
    if (! confirm('Delete this comment?')) return;
    try {
        await axios.delete(`/api/admin/comments/${comment.id}`);
        await load(currentPage.value);
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to delete comment.';
    }
};

watch(filter, () => load(1));

onMounted(() => load(1));
</script>
