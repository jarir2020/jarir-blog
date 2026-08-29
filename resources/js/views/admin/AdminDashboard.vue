<template>
    <div>
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-600 mt-1">
                Welcome back, {{ me?.name ?? 'admin' }}. Here's what's happening on the blog.
            </p>
        </header>

        <p v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
            {{ error }}
        </p>

        <p v-if="loading" class="text-sm text-gray-500">Loading dashboard…</p>

        <template v-if="!loading && stats">
            <!-- Stat tiles -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Posts</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.posts.total }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ stats.posts.published }} published ·
                        {{ stats.posts.draft }} draft ·
                        {{ stats.posts.archived }} archived
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Comments</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.comments.total }}</p>
                    <p class="text-xs mt-1">
                        <span
                            v-if="stats.comments.pending > 0"
                            class="text-red-600 font-medium"
                        >
                            {{ stats.comments.pending }} pending
                        </span>
                        <span v-else class="text-gray-500">No pending</span>
                        <span class="text-gray-400"> · {{ stats.comments.approved }} approved</span>
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Subscribers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.subscribers }}</p>
                    <p class="text-xs text-gray-500 mt-1">Newsletter signups</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Total views</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.posts.views.toLocaleString() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Across all published posts</p>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent posts -->
                <div class="bg-white rounded-lg shadow-sm">
                    <header class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Recent posts</h2>
                        <router-link
                            to="/admin/posts"
                            class="text-xs text-blue-600 hover:underline"
                        >View all</router-link>
                    </header>
                    <ul v-if="stats.recent_posts.length > 0" class="divide-y divide-gray-100">
                        <li
                            v-for="post in stats.recent_posts"
                            :key="post.id"
                            class="px-5 py-3 flex items-start justify-between gap-4"
                        >
                            <div class="min-w-0 flex-1">
                                <router-link
                                    :to="`/admin/posts/${post.id}`"
                                    class="text-sm text-gray-900 hover:text-blue-600 line-clamp-1"
                                >{{ post.title }}</router-link>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <span
                                        v-if="post.status"
                                        :style="statusStyle(post.status)"
                                        class="px-1.5 py-0.5 rounded-full text-xs"
                                    >{{ post.status.label }}</span>
                                    <span v-if="post.author" class="ml-2">{{ post.author.name }}</span>
                                </p>
                            </div>
                            <span class="text-xs text-gray-400 shrink-0">{{ formatDate(post.updated_at) }}</span>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-6 text-sm text-gray-500 text-center">
                        No posts yet.
                        <router-link to="/admin/posts/new" class="text-blue-600 hover:underline">Create the first one</router-link>.
                    </p>
                </div>

                <!-- Recent comments -->
                <div class="bg-white rounded-lg shadow-sm">
                    <header class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Recent comments</h2>
                        <router-link
                            to="/admin/comments"
                            class="text-xs text-blue-600 hover:underline"
                        >Moderate</router-link>
                    </header>
                    <ul v-if="stats.recent_comments.length > 0" class="divide-y divide-gray-100">
                        <li
                            v-for="comment in stats.recent_comments"
                            :key="comment.id"
                            class="px-5 py-3"
                        >
                            <div class="flex items-center justify-between gap-2 text-xs text-gray-500">
                                <span class="truncate">
                                    <strong class="text-gray-900">{{ comment.name }}</strong>
                                    <span class="ml-1">{{ comment.email }}</span>
                                </span>
                                <span class="shrink-0">{{ formatDate(comment.created_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1 line-clamp-2">{{ comment.body }}</p>
                            <p v-if="comment.post" class="text-xs text-gray-500 mt-1 truncate">
                                on
                                <router-link
                                    :to="{ name: 'BlogPost', params: { slug: comment.post.slug } }"
                                    class="text-blue-600 hover:underline"
                                >{{ comment.post.title }}</router-link>
                                <span
                                    v-if="!comment.approved"
                                    class="ml-2 px-1.5 py-0.5 rounded-full bg-yellow-100 text-yellow-800"
                                >Pending</span>
                            </p>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-6 text-sm text-gray-500 text-center">No comments yet.</p>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { formatDate } from '../../composables/format';

const loading = ref(true);
const error = ref(null);
const stats = ref(null);
const me = ref(null);

const load = async () => {
    loading.value = true;
    error.value = null;
    try {
        const [statsRes, meRes] = await Promise.all([
            axios.get('/api/admin/stats'),
            axios.get('/api/admin/me'),
        ]);
        stats.value = statsRes.data;
        me.value = meRes.data.user;
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load dashboard.';
    } finally {
        loading.value = false;
    }
};

// Render the status pill using the admin-configured color. Falls
// back to a neutral gray if a status somehow has no color set.
const statusStyle = (status) => {
    const color = status?.color || '#6b7280';
    return {
        backgroundColor: `${color}22`,
        color,
        border: `1px solid ${color}55`,
    };
};

onMounted(load);
</script>
