<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
            {{ isNew ? 'New post' : 'Edit post' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ success }}</p>

        <form class="bg-white shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input
                    id="title"
                    v-model="form.title"
                    type="text"
                    required
                    minlength="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <div>
                <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                <textarea
                    id="excerpt"
                    v-model="form.excerpt"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                <textarea
                    id="content"
                    v-model="form.content"
                    rows="12"
                    required
                    minlength="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="text-xs text-gray-500 mt-1">Use blank lines to separate paragraphs.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-1">Featured image URL</label>
                    <input
                        id="featured_image"
                        v-model="form.featured_image"
                        type="text"
                        placeholder="https://…"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center">
                        <input v-model="form.is_featured" type="checkbox" class="mr-2" />
                        <span class="text-sm text-gray-700">Featured on home</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categories</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="category in categories"
                        :key="category.id"
                        class="inline-flex items-center px-3 py-1.5 border rounded-md cursor-pointer"
                        :class="form.category_ids.includes(category.id) ? 'bg-blue-50 border-blue-300' : 'bg-white'"
                    >
                        <input
                            type="checkbox"
                            :value="category.id"
                            v-model="form.category_ids"
                            class="mr-1.5"
                        />
                        {{ category.name }}
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="tag in tags"
                        :key="tag.id"
                        class="inline-flex items-center px-3 py-1.5 border rounded-md cursor-pointer"
                        :class="form.tag_ids.includes(tag.id) ? 'bg-blue-50 border-blue-300' : 'bg-white'"
                    >
                        <input
                            type="checkbox"
                            :value="tag.id"
                            v-model="form.tag_ids"
                            class="mr-1.5"
                        />
                        #{{ tag.name }}
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="px-4 py-2 bg-white border rounded-md hover:bg-gray-50"
                    @click="$router.push('/admin/posts')"
                >Cancel</button>
                <button
                    type="submit"
                    :disabled="saving"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >{{ saving ? 'Saving…' : (isNew ? 'Create' : 'Update') }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const error = ref(null);
const success = ref(null);
const saving = ref(false);
const categories = ref([]);
const tags = ref([]);

const isNew = computed(() => route.params.id === 'new');

const form = ref({
    title: '',
    excerpt: '',
    content: '',
    status: 'draft',
    is_featured: false,
    featured_image: '',
    category_ids: [],
    tag_ids: [],
});

const loadMeta = async () => {
    const [{ data: catData }, { data: tagData }] = await Promise.all([
        axios.get('/api/categories'),
        axios.get('/api/admin/tags').catch(() => ({ data: { data: [] } })),
    ]);
    categories.value = Array.isArray(catData) ? catData : catData.data ?? [];
    tags.value = tagData.data ?? [];
};

const loadPost = async (id) => {
    if (isNew.value) return;
    const { data } = await axios.get(`/api/admin/posts/${id}`);
    const p = data.post;
    form.value = {
        title: p.title,
        excerpt: p.excerpt ?? '',
        content: p.content,
        status: p.status,
        is_featured: p.is_featured,
        featured_image: p.featured_image ?? '',
        category_ids: p.categories.map((c) => c.id),
        tag_ids: p.tags.map((t) => t.id),
    };
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/posts', form.value);
            success.value = 'Post created.';
            router.replace(`/admin/posts/${data.post.id}`);
        } else {
            await axios.put(`/api/admin/posts/${route.params.id}`, form.value);
            success.value = 'Post updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save post.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save post.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadMeta();
    if (! isNew.value) {
        try {
            await loadPost(route.params.id);
        } catch (e) {
            error.value = 'Post not found.';
        }
    }
});
</script>
