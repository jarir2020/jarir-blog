<template>
    <section class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
            Comments ({{ comments.length }})
        </h2>

        <form
            class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-4"
            @submit.prevent="submit"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Leave a comment</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="comment-name">Name</label>
                    <input
                        id="comment-name"
                        v-model="form.name"
                        type="text"
                        required
                        minlength="2"
                        maxlength="80"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="comment-email">Email</label>
                    <input
                        id="comment-email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="comment-body">Comment</label>
                <textarea
                    id="comment-body"
                    v-model="form.body"
                    required
                    minlength="2"
                    maxlength="4000"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            <p v-if="success" class="text-sm text-green-600 dark:text-green-400">{{ success }}</p>
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="submitting"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ submitting ? 'Posting…' : 'Post comment' }}
                </button>
            </div>
        </form>

        <ul v-if="comments.length > 0" class="space-y-4">
            <li
                v-for="comment in comments"
                :key="comment.id"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6"
            >
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ comment.name }}</h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(comment.created_at) }}</span>
                </div>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ comment.body }}</p>
            </li>
        </ul>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400">No comments yet. Be the first.</p>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { formatDate } from '../composables/format';
import useApi from '../composables/useApi';

const props = defineProps({
    postSlug: { type: String, required: true },
});

const api = useApi();
const comments = ref([]);
const form = ref({ name: '', email: '', body: '' });
const submitting = ref(false);
const error = ref(null);
const success = ref(null);

const load = async () => {
    try {
        const data = await api.getComments(props.postSlug);
        comments.value = data.data ?? [];
    } catch (e) {
        // Non-fatal — empty list is fine.
        comments.value = [];
    }
};

const submit = async () => {
    error.value = null;
    success.value = null;
    submitting.value = true;
    try {
        const created = await api.postComment(props.postSlug, form.value);
        comments.value.unshift(created);
        form.value = { name: '', email: '', body: '' };
        success.value = 'Thanks for commenting!';
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not post comment.';
        } else {
            error.value = e?.response?.data?.message ?? e?.message ?? 'Could not post comment.';
        }
    } finally {
        submitting.value = false;
    }
};

onMounted(load);
</script>
