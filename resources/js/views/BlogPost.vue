<template>
    <AppHeader />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <p v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ error }}
                </p>

                <p v-if="loading" class="mb-6 text-sm text-gray-500">Loading post…</p>

                <article v-if="post" class="bg-white rounded-lg shadow-md p-8">
                    <header class="mb-8">
                        <span v-if="primaryCategory" class="text-sm text-blue-600 font-semibold">
                            {{ primaryCategory.name }}
                        </span>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                            {{ post.title }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                            <span v-if="post.author">
                                By
                                <router-link
                                    :to="{ name: 'Author', params: { username: post.author.handle ?? authorHandle(post.author.name) } }"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ post.author.name }}
                                </router-link>
                            </span>
                            <span>{{ publishedDate }}</span>
                            <span>{{ readingTime }} min read</span>
                            <span>{{ post.views }} views</span>
                        </div>
                    </header>

                    <img
                        v-if="post.featured_image"
                        :src="post.featured_image"
                        :alt="post.title"
                        class="w-full h-96 object-cover rounded-lg mb-8"
                        @error="(e) => (e.target.style.display = 'none')"
                    />

                    <div v-if="post.excerpt" class="text-lg text-gray-600 italic mb-6">
                        {{ post.excerpt }}
                    </div>

                    <div class="prose prose-lg max-w-none">
                        <p
                            v-for="(paragraph, idx) in paragraphs"
                            :key="idx"
                            class="text-gray-700 leading-relaxed mb-6 whitespace-pre-line"
                        >
                            {{ paragraph }}
                        </p>
                    </div>

                    <div v-if="post.tags && post.tags.length > 0" class="mt-8 pt-6 border-t">
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in post.tags"
                                :key="tag.id"
                                class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full"
                            >
                                #{{ tag.name }}
                            </span>
                        </div>
                    </div>
                </article>

                <CommentList v-if="post" :post-slug="post.slug" />

                <section v-if="related.length > 0" class="mt-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Posts</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <PostCard
                            v-for="relatedPost in related"
                            :key="relatedPost.id"
                            :post="relatedPost"
                            @open="goToPost"
                        />
                    </div>
                </section>
            </div>

            <div class="lg:col-span-1">
                <Sidebar />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import CommentList from '../components/CommentList.vue';
import PostCard from '../components/PostCard.vue';
import Sidebar from '../components/Sidebar.vue';
import useApi from '../composables/useApi';
import { computeReadingTime, formatDateLong } from '../composables/format';

const route = useRoute();
const router = useRouter();
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

const goToPost = (relatedPost) => {
    if (!relatedPost?.slug) return;
    router.push({ name: 'BlogPost', params: { slug: relatedPost.slug } });
};

watch(
    () => route.params.slug,
    (slug) => {
        if (slug) loadPost(slug);
    },
    { immediate: false }
);

onMounted(() => {
    if (route.params.slug) loadPost(route.params.slug);
});
</script>

<style scoped>
.prose :deep(h2) {
    margin-top: 2rem;
    margin-bottom: 1rem;
}
</style>
