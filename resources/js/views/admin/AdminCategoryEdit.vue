<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 dark:text-gray-100 mb-6">
            {{ isNew ? 'New category' : 'Edit category' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ success }}</p>

        <form class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="cat-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        id="cat-name"
                        v-model="form.name"
                        type="text"
                        required
                        minlength="2"
                        maxlength="120"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label for="cat-slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input
                        id="cat-slug"
                        v-model="form.slug"
                        type="text"
                        maxlength="120"
                        placeholder="Auto-generated from name"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
            </div>

            <div>
                <label for="cat-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                <div class="flex items-center gap-2 max-w-md">
                    <input
                        id="cat-color"
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

            <div>
                <label for="cat-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea
                    id="cat-description"
                    v-model="form.description"
                    rows="3"
                    maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <router-link
                    to="/admin/settings/categories"
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

const isNew = computed(() => route.name === 'AdminCategoryNew');

const form = ref({
    name: '',
    slug: '',
    color: '#6b7280',
    description: '',
});

const loadCategory = async (id) => {
    if (isNew.value) return;
    const { data } = await axios.get(`/api/admin/categories/${id}`);
    const c = data.category;
    form.value = {
        name: c.name,
        slug: c.slug,
        color: c.color,
        description: c.description ?? '',
    };
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/categories', form.value);
            success.value = 'Category created.';
            router.replace(`/admin/settings/categories/${data.category.id}`);
        } else {
            await axios.put(`/api/admin/categories/${route.params.id}`, form.value);
            success.value = 'Category updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save category.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save category.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => loadCategory(route.params.id));
</script>
