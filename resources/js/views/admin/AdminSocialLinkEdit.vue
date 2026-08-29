<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            {{ isNew ? 'New social link' : 'Edit social link' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-700 dark:text-green-300">{{ success }}</p>

        <form class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="sl-platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform</label>
                    <select
                        id="sl-platform"
                        v-model="form.platform"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    >
                        <option value="facebook">Facebook</option>
                        <option value="x">X (Twitter)</option>
                        <option value="youtube">YouTube</option>
                        <option value="instagram">Instagram</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="github">GitHub</option>
                        <option value="rss">RSS</option>
                        <option value="custom">Custom (generic link icon)</option>
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        The icon in the public chrome is selected from the platform.
                    </p>
                </div>
                <div>
                    <label for="sl-label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                    <input
                        id="sl-label"
                        v-model="form.label"
                        type="text"
                        required
                        maxlength="80"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                </div>
            </div>

            <div>
                <label for="sl-url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                <input
                    id="sl-url"
                    v-model="form.url"
                    type="url"
                    required
                    maxlength="500"
                    placeholder="https://facebook.com/your-page"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="sl-order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Order</label>
                    <input
                        id="sl-order"
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
                    to="/admin/settings/social-links"
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

const isNew = computed(() => route.name === 'AdminSocialLinkNew');

const form = ref({
    platform: 'facebook',
    label: '',
    url: '',
    order: 0,
    enabled: true,
});

const loadLink = async (id) => {
    if (isNew.value) return;
    try {
        const { data } = await axios.get(`/api/admin/social-links/${id}`);
        const l = data.social_link;
        form.value = {
            platform: l.platform,
            label: l.label,
            url: l.url,
            order: l.order ?? 0,
            enabled: l.enabled,
        };
    } catch (e) {
        error.value = 'Social link not found.';
    }
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/social-links', form.value);
            success.value = 'Social link created.';
            router.replace(`/admin/settings/social-links/${data.social_link.id}`);
        } else {
            await axios.put(`/api/admin/social-links/${route.params.id}`, form.value);
            success.value = 'Social link updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save social link.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save social link.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => loadLink(route.params.id));
</script>
