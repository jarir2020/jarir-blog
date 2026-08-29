<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main column -->
            <div class="lg:col-span-2">
                <p v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ error }}
                </p>
                <p v-if="loading" class="text-sm text-gray-500">Loading post…</p>

                <article v-if="post" class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Featured image -->
                    <img
                        v-if="post.featured_image"
                        :src="post.featured_image"
                        :alt="post.title"
                        class="w-full h-72 md:h-96 object-cover"
                        @error="(e) => (e.target.style.display = 'none')"
                    />

                    <div class="p-6 md:p-8">
                        <router-link
                            v-if="primaryCategory"
                            :to="{ name: 'Category', params: { slug: primaryCategory.slug } }"
                            class="text-sm text-blue-600 font-semibold uppercase tracking-wider hover:underline"
                        >{{ primaryCategory.name }}</router-link>

                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                            {{ post.title }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-6">
                            <span v-if="post.author">
                                By
                                <router-link
                                    :to="{ name: 'Author', params: { username: post.author.handle ?? authorHandle(post.author.name) } }"
                                    class="text-blue-600 hover:underline"
                                >{{ post.author.name }}</router-link>
                            </span>
                            <span>{{ publishedDate }}</span>
                            <span>{{ readingTime }} min read</span>
                            <span>{{ post.views }} views</span>
                        </div>

                        <!-- Share bar 1 (below meta) -->
                        <div class="mb-6 pb-6 border-b">
                            <ShareBar :url="shareUrl" :title="post.title" label="Share:" />
                        </div>

                        <div v-if="post.excerpt" class="text-lg text-gray-600 italic mb-6">
                            {{ post.excerpt }}
                        </div>

                        <div class="prose prose-lg max-w-none">
                            <p
                                v-for="(paragraph, idx) in paragraphs"
                                :key="idx"
                                class="text-gray-700 leading-relaxed mb-6 whitespace-pre-line"
                            >{{ paragraph }}</p>
                        </div>

                        <!-- Share bar 2 (after body) -->
                        <div class="mt-6 pt-6 border-t">
                            <ShareBar :url="shareUrl" :title="post.title" label="Share this:" />
                        </div>

                        <!-- Tags -->
                        <div v-if="post.tags && post.tags.length > 0" class="mt-6 pt-6 border-t">
                            <div class="flex flex-wrap gap-2">
                                <router-link
                                    v-for="tag in post.tags"
                                    :key="tag.id"
                                    :to="{ name: 'Search', query: { q: tag.name } }"
                                    class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full hover:bg-blue-100 hover:text-blue-700"
                                >#{{ tag.name }}</router-link>
                            </div>
                        </div>
                    </div>
                </article>

                <CommentList v-if="post" :post-slug="post.slug" />

                <!-- Read Next -->
                <section v-if="related.length > 0" class="mt-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Read Next</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <PostCard
                            v-for="relatedPost in related"
                            :key="relatedPost.id"
                            :post="relatedPost"
                            variant="horizontal"
                        />
                    </div>
                </section>
            </div>

            <!-- Right sidebar -->
            <div class="lg:col-span-1">
                <Sidebar />
            </div>
        </div>

        <BackToTop />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import BackToTop from '../components/BackToTop.vue';
import CommentList from '../components/CommentList.vue';
import PostCard from '../components/PostCard.vue';
import ShareBar from '../components/ShareBar.vue';
import Sidebar from '../components/Sidebar.vue';
import useApi from '../composables/useApi';
import { formatDateLong } from '../composables/format';

const route = useRoute();
const api = useApi();

const loading = ref(true);
const error = ref(null);
const post = ref(null);
const related = ref([]);

const loadPost = async (slug) => {
    loading.value = true;
    error.value = null;
    post.value = null;
    related.value = [];
    try {
        const data = await api.getPost(slug);
        post.value = data.post ?? null;
        related.value = data.related ?? [];
    } catch (e) {
        if (e?.response?.status === 404) {
            error.value = 'Post not found.';
        } else {
            error.value = e?.response?.data?.message ?? e?.message ?? 'Failed to load post.';
        }
    } finally {
        loading.value = false;
    }
};

const primaryCategory = computed(() => {
    if (post.value && Array.isArray(post.value.categories) && post.value.categories.length > 0) {
        return post.value.categories[0];
    }
    return null;
});

const publishedDate = computed(() => formatDateLong(post.value?.published_at));

// We need a separate helper since `computeReadingTime` lives in
// format.js and we want a stable default. Reading time is on the
// server; the client side fallback is the word count / 200.
import { computeReadingTime } from '../composables/format';
const readingTime = computed(() => {
    if (post.value?.reading_time) return post.value.reading_time;
    return computeReadingTime(post.value?.content);
});

const paragraphs = computed(() => {
    if (!post.value?.content) return [];
    return String(post.value.content)
        .split(/\n{2,}/)
        .map((p) => p.trim())
        .filter(Boolean);
});

const authorHandle = (name) => {
    if (!name) return '';
    return name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
};

// Computed lazily — we don't have a Vue Router import here, but
// `window.location` works fine for a fully-qualified share URL.
const shareUrl = computed(() => {
    if (!post.value) return '';
    if (typeof window === 'undefined') return '';
    return `${window.location.origin}/blog/${post.value.slug}`;
});

watch(
    () => route.params.slug,
    (slug) => {
        if (slug) loadPost(slug);
    },
);

onMounted(() => {
    if (route.params.slug) loadPost(route.params.slug);
});
</script>
