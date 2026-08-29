<template>
    <aside class="space-y-6">
        <!--
          Each enabled widget in the order configured by the admin.
          We dispatch on `widget.type` to render the right template.
          Unknown / unimplemented types fall through to a placeholder.
        -->
        <template v-for="widget in data.widgets" :key="widget.id || widget.type">
            <!-- Tabbed Popular / Recent / Comments -->
            <section
                v-if="widget.type === 'popular_recent_comments'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">{{ widget.name }}</h3>
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-3 text-sm">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="activeTab = tab.key"
                        :class="[
                            'px-3 py-2 -mb-px border-b-2 font-medium',
                            activeTab === tab.key
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100',
                        ]"
                    >{{ tab.label }}</button>
                </div>
                <ul v-if="(widget[activeTab] || []).length > 0" class="space-y-3">
                    <li v-for="item in widget[activeTab]" :key="item.id" class="flex gap-3 text-sm">
                        <img
                            v-if="item.featured_image"
                            :src="item.featured_image"
                            :alt="item.title"
                            class="w-16 h-12 object-cover rounded shrink-0"
                            @error="(e) => e.target.style.display = 'none'"
                        />
                        <div class="min-w-0 flex-1">
                            <router-link
                                v-if="item.post"
                                :to="{ name: 'BlogPost', params: { slug: item.post.slug } }"
                                class="text-gray-800 dark:text-gray-200 hover:text-blue-600 line-clamp-2"
                            >{{ item.post.title }}</router-link>
                            <router-link
                                v-else
                                :to="{ name: 'BlogPost', params: { slug: item.slug } }"
                                class="text-gray-800 dark:text-gray-200 hover:text-blue-600 line-clamp-2"
                            >{{ item.title }}</router-link>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <template v-if="activeTab === 'popular'">{{ item.views }} views</template>
                                <template v-else-if="activeTab === 'comments'">{{ item.name }}</template>
                                <template v-else>{{ formatDate(item.published_at) }}</template>
                            </p>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">Nothing here yet.</p>
            </section>

            <!-- Category widget: heading + 5 recent posts in that category -->
            <section
                v-else-if="widget.type === 'category'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">
                    <router-link
                        v-if="widget.category"
                        :to="{ name: 'Category', params: { slug: widget.category.slug } }"
                        class="hover:text-blue-600"
                    >{{ widget.name }}</router-link>
                    <template v-else>{{ widget.name }}</template>
                </h3>
                <ul v-if="widget.posts.length > 0" class="space-y-3">
                    <li v-for="post in widget.posts" :key="post.id" class="flex gap-3 text-sm">
                        <img
                            v-if="post.featured_image"
                            :src="post.featured_image"
                            :alt="post.title"
                            class="w-16 h-12 object-cover rounded shrink-0"
                            @error="(e) => e.target.style.display = 'none'"
                        />
                        <router-link
                            :to="{ name: 'BlogPost', params: { slug: post.slug } }"
                            class="text-gray-800 dark:text-gray-200 hover:text-blue-600 line-clamp-2"
                        >{{ post.title }}</router-link>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No posts in this category yet.</p>
            </section>

            <!-- Video gallery (placeholder; YouTube channel id is in widget.settings) -->
            <section
                v-else-if="widget.type === 'video'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">{{ widget.name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Video gallery coming soon.</p>
            </section>

            <!-- Arbitrary HTML -->
            <section
                v-else-if="widget.type === 'html'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 v-if="widget.name" class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">{{ widget.name }}</h3>
                <div class="text-sm text-gray-700 dark:text-gray-300" v-html="widget.body"></div>
            </section>

            <!-- Social follow -->
            <section
                v-else-if="widget.type === 'social'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">{{ widget.name }}</h3>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-for="(link, idx) in widget.links"
                        :key="idx"
                        :href="link.url"
                        target="_blank"
                        rel="noopener"
                        class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-blue-100 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-200"
                    >{{ link.platform }}</a>
                </div>
            </section>

            <!-- Archives dropdown -->
            <section
                v-else-if="widget.type === 'archives'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-3">{{ widget.name }}</h3>
                <select
                    v-if="widget.archives.length > 0"
                    @change="onArchiveChange"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                >
                    <option value="">Select month</option>
                    <option v-for="a in widget.archives" :key="a.key" :value="a.key">
                        {{ a.label }} ({{ a.count }})
                    </option>
                </select>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No archives yet.</p>
            </section>

            <!-- Newsletter -->
            <section
                v-else-if="widget.type === 'newsletter'"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">{{ widget.name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Get new posts in your inbox.</p>
                <form @submit.prevent="subscribe" class="space-y-2">
                    <input
                        v-model="email"
                        type="email"
                        required
                        placeholder="you@example.com"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
        </template>

        <p v-if="!loading && data.widgets.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            No sidebar widgets configured.
        </p>
    </aside>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { formatDate } from '../composables/format';
import useApi from '../composables/useApi';

const router = useRouter();
const api = useApi();

const loading = ref(true);
const data = ref({ widgets: [] });
const activeTab = ref('popular');
const tabs = [
    { key: 'popular', label: 'Popular' },
    { key: 'recent', label: 'Recent' },
    { key: 'comments', label: 'Comments' },
];

const email = ref('');
const submitting = ref(false);
const message = ref(null);
const messageClass = ref('');

const load = async () => {
    try {
        const payload = await api.getSidebar();
        data.value = { widgets: payload.widgets ?? [] };
    } catch (e) {
        // Silent: sidebar is non-critical.
    } finally {
        loading.value = false;
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

const onArchiveChange = (e) => {
    const key = e.target.value;
    if (!key) return;
    // `key` is "YYYY-MM" — push it to the search route.
    router.push({ name: 'Search', query: { archive: key } });
};

onMounted(load);
</script>
