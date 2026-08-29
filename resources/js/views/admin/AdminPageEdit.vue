<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            {{ isNew ? 'New page' : 'Edit page' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-700 dark:text-green-300">{{ success }}</p>

        <form class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="p-slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input
                        id="p-slug"
                        v-model="form.slug"
                        type="text"
                        required
                        maxlength="80"
                        placeholder="about / about/our-mission / contact"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        The full URL path. Use <code class="font-mono">about</code> for the index,
                        <code class="font-mono">about/our-mission</code> for a sub-page, etc.
                    </p>
                </div>
                <div>
                    <label for="p-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input
                        id="p-title"
                        v-model="form.title"
                        type="text"
                        required
                        maxlength="160"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                </div>
            </div>

            <div>
                <label for="p-excerpt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Excerpt <span class="text-gray-400 font-normal">(optional, shown on the about index)</span>
                </label>
                <input
                    id="p-excerpt"
                    v-model="form.excerpt"
                    type="text"
                    maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                />
            </div>

            <!-- Hero image: uploaded to /api/admin/images and the
                 returned URL is saved as hero_image. We also accept
                 a URL paste in case the admin wants to point at an
                 existing image. -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hero image</label>
                <div v-if="form.hero_image" class="mb-2">
                    <img
                        :src="form.hero_image"
                        alt=""
                        class="w-full max-w-md h-32 object-cover rounded border border-gray-200 dark:border-gray-700"
                        @error="(e) => (e.target.style.display = 'none')"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600">
                        <span v-if="uploading">Uploading…</span>
                        <span v-else>Upload image</span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            class="hidden"
                            @change="upload"
                        />
                    </label>
                    <input
                        v-model="form.hero_image"
                        type="url"
                        placeholder="…or paste an image URL"
                        class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm"
                    />
                    <button
                        v-if="form.hero_image"
                        type="button"
                        class="px-2 py-2 text-sm text-red-600 dark:text-red-400 hover:underline"
                        @click="form.hero_image = ''"
                    >Remove</button>
                </div>
            </div>

            <div>
                <label for="p-body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body (Markdown)</label>
                <textarea
                    id="p-body"
                    v-model="form.body"
                    rows="14"
                    required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Standard Markdown: <code class="font-mono"># Heading</code>,
                    <code class="font-mono">**bold**</code>, <code class="font-mono">[link](url)</code>, lists. HTML is stripped for safety.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="p-parent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent slug</label>
                    <select
                        id="p-parent"
                        v-model="form.parent_slug"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm"
                    >
                        <option value="">(none — top-level page)</option>
                        <option v-for="p in parentOptions" :key="p.slug" :value="p.slug">{{ p.slug }} — {{ p.title }}</option>
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used to group on index pages.</p>
                </div>
                <div>
                    <label for="p-order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Order</label>
                    <input
                        id="p-order"
                        v-model.number="form.order"
                        type="number"
                        min="0"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lower numbers appear first.</p>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center">
                        <input v-model="form.enabled" type="checkbox" class="mr-2" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Enabled (visible on public site)</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <router-link
                    to="/admin/settings/pages"
                    class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                >Cancel</router-link>
                <button
                    type="submit"
                    :disabled="saving"
                    class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                >{{ saving ? 'Saving…' : isNew ? 'Create' : 'Update' }}</button>
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
const uploading = ref(false);
const allPages = ref([]);

const isNew = computed(() => route.name === 'AdminPageNew');

const form = ref({
    slug: '',
    title: '',
    excerpt: '',
    hero_image: '',
    body: '',
    parent_slug: '',
    order: 0,
    enabled: true,
});

const parentOptions = computed(() =>
    allPages.value
        .filter((p) => !p.parent_slug && p.id !== (Number(route.params.id) || 0))
        .map((p) => ({ slug: p.slug, title: p.title })),
);

const loadAllPages = async () => {
    try {
        const { data } = await axios.get('/api/admin/pages', { params: { per_page: 100 } });
        allPages.value = data.data ?? [];
    } catch {
        allPages.value = [];
    }
};

const loadPage = async (id) => {
    if (isNew.value) return;
    try {
        const { data } = await axios.get(`/api/admin/pages/${id}`);
        const p = data.page;
        form.value = {
            slug: p.slug,
            title: p.title,
            excerpt: p.excerpt ?? '',
            hero_image: p.hero_image ?? '',
            body: p.body,
            parent_slug: p.parent_slug ?? '',
            order: p.order ?? 0,
            enabled: p.enabled,
        };
    } catch (e) {
        error.value = 'Page not found.';
    }
};

const upload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    error.value = null;
    try {
        const fd = new FormData();
        fd.append('image', file);
        const { data } = await axios.post('/api/admin/images', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        form.value.hero_image = data.url;
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Image upload failed.';
    } finally {
        uploading.value = false;
        // Reset the file input so uploading the same file again fires
        // the change event.
        event.target.value = '';
    }
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        const payload = { ...form.value, parent_slug: form.value.parent_slug || null };
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/pages', payload);
            success.value = 'Page created.';
            router.replace(`/admin/settings/pages/${data.page.id}`);
        } else {
            await axios.put(`/api/admin/pages/${route.params.id}`, payload);
            success.value = 'Page updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save page.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save page.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadAllPages();
    await loadPage(route.params.id);
});
</script>
