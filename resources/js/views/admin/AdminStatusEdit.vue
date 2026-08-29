<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 dark:text-gray-100 mb-6">
            {{ isNew ? 'New status' : 'Edit status' }}
        </h2>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ success }}</p>

        <form class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4" @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="status-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        id="status-name"
                        v-model="form.name"
                        type="text"
                        required
                        minlength="2"
                        maxlength="80"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label for="status-slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input
                        id="status-slug"
                        v-model="form.slug"
                        type="text"
                        maxlength="80"
                        placeholder="Auto-generated from name"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Used by the public site (e.g. <code class="font-mono">published</code>). Don't change unless you know what you're doing.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="status-label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                    <input
                        id="status-label"
                        v-model="form.label"
                        type="text"
                        maxlength="80"
                        placeholder="Defaults to Name"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label for="status-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                    <div class="flex items-center gap-2">
                        <input
                            id="status-color"
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
                    <label for="status-order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Order</label>
                    <input
                        id="status-order"
                        v-model.number="form.order"
                        type="number"
                        min="0"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lower numbers appear first.</p>
                </div>
            </div>

            <div>
                <label for="status-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea
                    id="status-description"
                    v-model="form.description"
                    rows="2"
                    maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <router-link
                    to="/admin/settings/statuses"
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

const isNew = computed(() => route.name === 'AdminStatusNew');

const form = ref({
    name: '',
    slug: '',
    label: '',
    color: '#6b7280',
    description: '',
    order: 0,
});

const loadStatus = async (id) => {
    if (isNew.value) return;
    const { data } = await axios.get(`/api/admin/statuses/${id}`);
    const s = data.status;
    form.value = {
        name: s.name,
        slug: s.slug,
        label: s.label,
        color: s.color,
        description: s.description ?? '',
        order: s.order ?? 0,
    };
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        if (isNew.value) {
            const { data } = await axios.post('/api/admin/statuses', form.value);
            success.value = 'Status created.';
            router.replace(`/admin/settings/statuses/${data.status.id}`);
        } else {
            await axios.put(`/api/admin/statuses/${route.params.id}`, form.value);
            success.value = 'Status updated.';
        }
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save status.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save status.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => loadStatus(route.params.id));
</script>
