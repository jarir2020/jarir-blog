<template>
    <article
        :class="[
            'bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow border border-gray-100 dark:border-gray-700',
            variant === 'horizontal' ? 'flex flex-row' : 'flex flex-col h-full',
        ]"
    >
        <router-link
            :to="{ name: 'BlogPost', params: { slug: post.slug } }"
            :class="[
                'block shrink-0',
                variant === 'horizontal' ? 'w-32 h-24 sm:w-40 sm:h-28' : 'w-full h-44',
            ]"
        >
            <img
                :src="post.featured_image || fallbackImage"
                :alt="post.title"
                :class="[
                    'w-full h-full object-cover',
                    variant === 'horizontal' ? 'rounded-l-lg' : 'rounded-t-lg',
                ]"
                @error="(e) => (e.target.src = fallbackImage)"
            />
        </router-link>
        <div :class="['flex-1 min-w-0', variant === 'horizontal' ? 'p-4' : 'p-5']">
            <router-link
                v-if="primaryCategory"
                :to="{ name: 'Category', params: { slug: primaryCategory.slug } }"
                class="text-xs font-semibold text-blue-600 uppercase tracking-wider hover:underline"
            >{{ primaryCategory.name }}</router-link>
            <h3
                :class="[
                    'font-bold text-gray-900 dark:text-gray-100 mt-1 line-clamp-2',
                    variant === 'horizontal' ? 'text-sm' : 'text-lg',
                ]"
            >
                <router-link
                    :to="{ name: 'BlogPost', params: { slug: post.slug } }"
                    class="hover:text-blue-600"
                >{{ post.title }}</router-link>
            </h3>
            <p
                v-if="post.excerpt && variant !== 'horizontal'"
                class="text-gray-600 dark:text-gray-400 text-sm mt-2 line-clamp-2"
            >{{ post.excerpt }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="post.author">{{ post.author.name }}</span>
                <span v-if="post.author" aria-hidden="true">·</span>
                <span>{{ formattedDate }}</span>
                <span v-if="post.reading_time" aria-hidden="true">·</span>
                <span v-if="post.reading_time">{{ post.reading_time }} min read</span>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '../composables/format';

const props = defineProps({
    post: { type: Object, required: true },
    // 'vertical' (default) is the home / category list card.
    // 'horizontal' is the smaller card used in the "Read Next" grid
    // and the sidebar's category widget.
    variant: {
        type: String,
        default: 'vertical',
        validator: (v) => ['vertical', 'horizontal'].includes(v),
    },
});

const fallbackImage = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200"><rect width="100%" height="100%" fill="%23e5e7eb"/><text x="50%" y="50%" fill="%239ca3af" font-family="sans-serif" font-size="18" text-anchor="middle" dominant-baseline="middle">No image</text></svg>';

const primaryCategory = computed(() => {
    if (Array.isArray(props.post.categories) && props.post.categories.length > 0) {
        return props.post.categories[0];
    }
    return null;
});

const formattedDate = computed(() => formatDate(props.post.published_at));
</script>
