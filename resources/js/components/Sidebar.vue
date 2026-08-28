<template>
    <aside class="space-y-8">
        <!-- Recent posts -->
        <section class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Posts</h3>
            <ul v-if="data.recent.length > 0" class="space-y-3">
                <li v-for="post in data.recent" :key="post.id" class="text-sm">
                    <router-link
                        :to="{ name: 'BlogPost', params: { slug: post.slug } }"
                        class="text-gray-800 hover:text-blue-600 line-clamp-2"
                    >
                        {{ post.title }}
                    </router-link>
                    <p class="text-xs text-gray-500 mt-1">{{ formatDate(post.published_at) }}</p>
                </li>
            </ul>
            <p v-else class="text-sm text-gray-500">No posts yet.</p>
        </section>

        <!-- Popular posts -->
        <section class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Popular Posts</h3>
            <ul v-if="data.popular.length > 0" class="space-y-3">
                <li v-for="post in data.popular" :key="post.id" class="text-sm">
                    <router-link
                        :to="{ name: 'BlogPost', params: { slug: post.slug } }"
                        class="text-gray-800 hover:text-blue-600 line-clamp-2"
                    >
                        {{ post.title }}
                    </router-link>
                    <p class="text-xs text-gray-500 mt-1">{{ post.views }} views</p>
                </li>
            </ul>
            <p v-else class="text-sm text-gray-500">No popular posts yet.</p>
        </section>

        <!-- Tag cloud -->
        <section class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Tags</h3>
            <div v-if="data.tags.length > 0" class="flex flex-wrap gap-2">
                <router-link
                    v-for="tag in data.tags"
                    :key="tag.id"
                    :to="{ name: 'Search', query: { q: tag.name } }"
                    class="px-2.5 py-1 text-xs bg-gray-100 text-gray-700 rounded-full hover:bg-blue-100 hover:text-blue-700"
                >
                    #{{ tag.name }}
                    <span class="text-gray-400">({{ tag.posts_count }})</span>
                </router-link>
            </div>
            <p v-else class="text-sm text-gray-500">No tags yet.</p>
        </section>

        <!-- Newsletter -->
        <section class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Subscribe</h3>
            <p class="text-sm text-gray-600 mb-3">Get new posts in your inbox.</p>
            <form @submit.prevent="subscribe" class="space-y-2">
                <input
                    v-model="email"
                    type="email"
                    required
                    placeholder="you@example.com"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ submitting ? 'Subscribing…' : 'Subscribe' }}
                </button>
                <p v-if="message" :class="messageClass" class="text-xs">{{ message }}</p>
            </form>
        </section>
    </aside>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { formatDate } from '../composables/format';
import useApi from '../composables/useApi';

const api = useApi();
const data = ref({ recent: [], popular: [], tags: [] });

const email = ref('');
const submitting = ref(false);
const message = ref(null);
const messageClass = ref('');

const load = async () => {
    try {
        const payload = await api.getSidebar();
        data.value = {
            recent: payload.recent ?? [],
            popular: payload.popular ?? [],
            tags: payload.tags ?? [],
        };
    } catch (e) {
        // Silent failure — sidebar is non-critical.
    }
};

const subscribe = async () => {
    submitting.value = true;
    message.value = null;
    try {
        await api.subscribe(email.value);
        messageClass.value = 'text-green-600';
        message.value = 'Thanks! Check your inbox to confirm.';
        email.value = '';
    } catch (e) {
        messageClass.value = 'text-red-600';
        message.value = e?.response?.data?.errors?.email?.[0]
            ?? e?.response?.data?.message
            ?? 'Could not subscribe. Try again later.';
    } finally {
        submitting.value = false;
    }
};

onMounted(load);
</script>
