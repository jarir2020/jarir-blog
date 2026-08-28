<template>
    <article
        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
        @click="$emit('open', post)"
    >
        <img
            :src="post.featured_image || fallbackImage"
            :alt="post.title"
            class="w-full h-48 object-cover"
            @error="(e) => (e.target.src = fallbackImage)"
        />
        <div class="p-6">
            <span v-if="primaryCategory" class="text-sm text-blue-600 font-semibold">
                {{ primaryCategory.name }}
            </span>
            <h3 class="text-xl font-bold text-gray-900 mt-2 mb-2">{{ post.title }}</h3>
            <p v-if="post.excerpt" class="text-gray-600 text-sm mb-4">{{ post.excerpt }}</p>
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span v-if="post.author">{{ post.author.name }}</span>
                <span>{{ formattedDate }}</span>
            </div>
            <div v-if="readingTime" class="mt-2 text-xs text-gray-500">
                {{ readingTime }} min read
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { computeReadingTime, formatDate } from '../composables/format';

const props = defineProps({
    post: { type: Object, required: true },
});

defineEmits(['open']);

const fallbackImage = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200"><rect width="100%" height="100%" fill="%23e5e7eb"/><text x="50%" y="50%" fill="%239ca3af" font-family="sans-serif" font-size="18" text-anchor="middle" dominant-baseline="middle">No image</text></svg>';

const primaryCategory = computed(() => {
    if (Array.isArray(props.post.categories) && props.post.categories.length > 0) {
        return props.post.categories[0];
    }
    return null;
});

const formattedDate = computed(() => formatDate(props.post.published_at));

// Trust the server's reading_time when present; fall back to client-side
// computation for posts that came from a source that didn't include it.
const readingTime = computed(() => {
    if (props.post.reading_time) return props.post.reading_time;
    return computeReadingTime(props.post.content);
});
</script>
