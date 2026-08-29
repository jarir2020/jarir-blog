<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
            {{ isNew ? 'New widget' : 'Edit widget' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ success }}</p>

        <form class="bg-white shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="w-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                        id="w-name"
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="80"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label for="w-type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select
                        id="w-type"
                        v-model="form.type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    >
                        <option value="popular_recent_comments">Popular / Recent / Comments</option>
                        <option value="category">Category list</option>
                        <option value="video">Video gallery</option>
                        <option value="html">Custom HTML</option>
                        <option value="social">Social follow</option>
                        <option value="archives">Archives</option>
                        <option value="newsletter">Newsletter</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Changing the type replaces the current settings — re-enter them below.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="w-position" class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                    <select
                        id="w-position"
                        v-model="form.position"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    >
                        <option value="right">Right sidebar</option>
                        <option value="left">Left sidebar</option>
                    </select>
                </div>
                <div>
                    <label for="w-order" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                    <input
                        id="w-order"
                        v-model.number="form.order"
                        type="number"
                        min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center">
                        <input v-model="form.enabled" type="checkbox" class="mr-2" />
                        <span class="text-sm text-gray-700">Enabled (visible on public site)</span>
                    </label>
                </div>
            </div>

            <!-- Type-specific settings editor -->
            <div class="pt-4 border-t">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Settings</h3>

                <div v-if="form.type === 'category'" class="space-y-3">
                    <div>
                        <label for="w-cat" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select
                            id="w-cat"
                            v-model.number="settings.category_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                        >
                            <option :value="null">Select a category…</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="w-cat-title" class="block text-sm font-medium text-gray-700 mb-1">Display title (optional)</label>
                        <input
                            id="w-cat-title"
                            v-model="settings.title"
                            type="text"
                            maxlength="80"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                        />
                    </div>
                </div>

                <div v-else-if="form.type === 'html'" class="space-y-3">
                    <label for="w-html" class="block text-sm font-medium text-gray-700 mb-1">Body (HTML allowed)</label>
                    <textarea
                        id="w-html"
                        v-model="settings.body"
                        rows="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm"
                    />
                    <p class="text-xs text-gray-500">Rendered with <code class="font-mono">v-html</code>. Be careful — the content is output as-is.</p>
                </div>

                <div v-else-if="form.type === 'social'" class="space-y-3">
                    <p class="text-sm text-gray-500">Add (platform, url) pairs.</p>
                    <div v-for="(link, idx) in settings.links" :key="idx" class="flex gap-2 items-center">
                        <input
                            v-model="link.platform"
                            type="text"
                            placeholder="facebook"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm"
                        />
                        <input
                            v-model="link.url"
                            type="url"
                            placeholder="https://facebook.com/…"
                            class="flex-[2] px-3 py-2 border border-gray-300 rounded-md text-sm"
                        />
                        <button
                            type="button"
                            @click="settings.links.splice(idx, 1)"
                            class="px-2 py-2 text-sm text-red-600 hover:underline"
                        >Remove</button>
                    </div>
                    <button
                        type="button"
                        @click="settings.links.push({ platform: '', url: '' })"
                        class="px-3 py-1.5 text-sm text-blue-600 hover:underline"
                    >+ Add link</button>
                </div>

                <div v-else-if="form.type === 'video'" class="space-y-3">
                    <label for="w-vid" class="block text-sm font-medium text-gray-700 mb-1">YouTube channel id (placeholder)</label>
                    <input
                        id="w-vid"
                        v-model="settings.placeholder"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                    />
                    <p class="text-xs text-gray-500">The video gallery is a placeholder in this version — settings are stored but not yet rendered.</p>
                </div>

                <p v-else class="text-sm text-gray-500">No type-specific settings for this widget type.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <router-link
                    to="/admin/settings/widgets"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
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
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const error = ref(null);
const success = ref(null);
const saving = ref(false);
const categories = ref([]);

const isNew = computed(() => route.name === 'AdminWidgetNew');

const form = ref({
    name: '',
    type: 'popular_recent_comments',
    position: 'right',
    order: 0,
    enabled: true,
});

// Type-specific settings. Reset when the type changes so the user
// doesn't see stale fields from the previous type.
const settings = ref({});
watch(
    () => form.value.type,
    (t) => {
        if (t === 'category') settings.value = { category_id: null, title: '' };
        else if (t === 'html') settings.value = { body: '' };
        else if (t === 'social') settings.value = { links: [] };
        else if (t === 'video') settings.value = { placeholder: '' };
        else settings.value = {};
    },
    { immediate: true },
);

const loadCategories = async () => {
    try {
        const { data } = await axios.get('/api/categories', { headers: { Accept: 'application/json' } });
        categories.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch {
        categories.value = [];
    }
};

const loadWidget = async (id) => {
    if (isNew.value) return;
    try {
        const { data } = await axios.get(`/api/admin/widgets/${id}`);
        const w = data.widget;
        form.value = {
            name: w.name,
            type: w.type,
            position: w.position,
            order: w.order,
            enabled: w.enabled,
        };
        // `settings` is initialised by the type watcher; assign the
        // loaded values after a tick so the watcher has run.
        await new Promise((r) => setTimeout(r, 0));
        settings.value = w.settings ?? {};
    } catch (e) {
        error.value = 'Widget not found.';
    }
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        const payload = { ...form.value, settings: settings.value };
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/widgets', payload);
            success.value = 'Widget created.';
            router.replace(`/admin/settings/widgets/${data.widget.id}`);
        } else {
            await axios.put(`/api/admin/widgets/${route.params.id}`, payload);
            success.value = 'Widget updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save widget.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save widget.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadCategories();
    await loadWidget(route.params.id);
});
</script>
