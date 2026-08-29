<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 dark:text-gray-100 mb-6">
            {{ isNew ? 'New tag' : 'Edit tag' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ success }}</p>

        <form class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tag-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        id="tag-name"
                        v-model="form.name"
                        type="text"
                        required
                        minlength="2"
                        maxlength="60"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label for="tag-slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input
                        id="tag-slug"
                        v-model="form.slug"
                        type="text"
                        maxlength="60"
                        placeholder="Auto-generated from name"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
            </div>

            <div>
                <label for="tag-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                <div class="flex items-center gap-2 max-w-md">
                    <input
                        id="tag-color"
                        v-model="form.color"
                        type="color"
                        class="h-10 w-12 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer"
                    />
                    <input
                        v-model="form.color"
                        type="text"
                        maxlength="7"
                        pattern="^#[0-9a-fA-F]{6}$"
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <router-link
                    to="/admin/settings/tags"
                    class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                >Cancel</router-link>
                <button
                    type="submit"
                    :disabled="saving"
                    class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ saving ? 'Saving…' : isNew ? 'Create' : 'Update' }}
                </button>
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

const isNew = computed(() => route.name === 'AdminTagNew');

const form = ref({
    name: '',
    slug: '',
    color: '#6b7280',
});

const loadTag = async (id) => {
    if (isNew.value) return;
    // The admin tags index already returns the row we need. We hit
    // it with a tiny `per_page` filter rather than the show endpoint
    // (which we don't have for tags) — fine for small lists.
    const { data } = await axios.get('/api/admin/tags', { params: { per_page: 100 } });
    const t = (data.data ?? []).find((x) => String(x.id) === String(id));
    if (! t) {
        error.value = 'Tag not found.';
        return;
    }
    form.value = {
        name: t.name,
        slug: t.slug,
        color: t.color,
    };
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/tags', form.value);
            success.value = 'Tag created.';
            router.replace(`/admin/settings/tags/${data.tag.id}`);
        } else {
            await axios.put(`/api/admin/tags/${route.params.id}`, form.value);
            success.value = 'Tag updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save tag.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save tag.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => loadTag(route.params.id));
</script>
