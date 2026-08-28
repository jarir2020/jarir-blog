<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Category: {{ categoryName }}</h1>
            <p class="text-gray-600 mt-2">Browse all posts in this category</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="post in posts" :key="post.id"
                 class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                 @click="goToPost(post)">
                <img :src="post.image" :alt="post.title" class="w-full h-48 object-cover">
                <div class="p-6">
                    <span class="text-sm text-blue-600 font-semibold">{{ post.category }}</span>
                    <h3 class="text-xl font-bold text-gray-900 mt-2 mb-2">{{ post.title }}</h3>
                    <p class="text-gray-600 text-sm mb-4">{{ post.excerpt }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ post.date }}</span>
                        <span class="text-blue-600 text-sm font-medium">Read More →</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-12 flex justify-center">
            <nav class="flex gap-2">
                <button class="px-4 py-2 bg-white border rounded hover:bg-gray-50">Previous</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">1</button>
                <button class="px-4 py-2 bg-white border rounded hover:bg-gray-50">2</button>
                <button class="px-4 py-2 bg-white border rounded hover:bg-gray-50">3</button>
                <button class="px-4 py-2 bg-white border rounded hover:bg-gray-50">Next</button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const categoryName = computed(() => {
    return route.params.slug.charAt(0).toUpperCase() + route.params.slug.slice(1);
});

const posts = ref([
    {
        id: 1,
        title: 'Getting Started with Vue.js 3',
        excerpt: 'Learn the basics of Vue.js 3 and its composition API.',
        category: 'Technology',
        date: 'Dec 10, 2024',
        image: 'https://picsum.photos/seed/vue1/400/200'
    },
    {
        id: 2,
        title: 'Laravel Best Practices',
        excerpt: 'Tips for writing clean and maintainable Laravel code.',
        category: 'Technology',
        date: 'Dec 8, 2024',
        image: 'https://picsum.photos/seed/laravel1/400/200'
    },
    {
        id: 3,
        title: 'Modern Web Development',
        excerpt: 'Exploring the latest trends in web development.',
        category: 'Technology',
        date: 'Dec 5, 2024',
        image: 'https://picsum.photos/seed/web1/400/200'
    }
]);

const goToPost = (post) => {
    router.push({ name: 'BlogPost', params: { slug: `post-${post.id}` } });
};
</script>
