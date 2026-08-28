<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <article class="bg-white rounded-lg shadow-md p-8">
            <!-- Post Header -->
            <header class="mb-8">
                <span class="text-sm text-blue-600 font-semibold">{{ post.category }}</span>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">{{ post.title }}</h1>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span>By {{ post.author }}</span>
                    <span>{{ post.date }}</span>
                    <span>{{ post.readTime }} min read</span>
                </div>
            </header>

            <!-- Featured Image -->
            <img :src="post.image" :alt="post.title" class="w-full h-96 object-cover rounded-lg mb-8">

            <!-- Post Content -->
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 leading-relaxed mb-6">{{ post.content }}</p>
                <p class="text-gray-700 leading-relaxed mb-6">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                </p>
                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Key Points</h2>
                <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                    <li>Important insight about the topic</li>
                    <li>Another valuable perspective to consider</li>
                    <li>Practical advice for implementation</li>
                    <li>Future trends and predictions</li>
                </ul>
                <p class="text-gray-700 leading-relaxed mb-6">
                    Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                </p>
            </div>

            <!-- Tags -->
            <div class="mt-8 pt-6 border-t">
                <div class="flex flex-wrap gap-2">
                    <span v-for="tag in post.tags" :key="tag" 
                          class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full hover:bg-gray-200 cursor-pointer">
                        {{ tag }}
                    </span>
                </div>
            </div>

            <!-- Share -->
            <div class="mt-8 pt-6 border-t">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Share this article</h3>
                <div class="flex gap-4">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Facebook</button>
                    <button class="px-4 py-2 bg-sky-500 text-white rounded hover:bg-sky-600">Twitter</button>
                    <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">WhatsApp</button>
                    <button class="px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800">LinkedIn</button>
                </div>
            </div>
        </article>

        <!-- Related Posts -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="relatedPost in relatedPosts" :key="relatedPost.id"
                     class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                     @click="goToPost(relatedPost)">
                    <img :src="relatedPost.image" :alt="relatedPost.title" class="w-full h-40 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-gray-900">{{ relatedPost.title }}</h3>
                        <p class="text-gray-600 text-sm mt-2">{{ relatedPost.excerpt }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const post = ref({
    id: 1,
    title: 'The Future of Technology in 2024',
    category: 'Technology',
    author: 'John Doe',
    date: 'December 15, 2024',
    readTime: '5',
    image: 'https://picsum.photos/seed/tech1/800/400',
    content: 'Explore the emerging technologies that will shape our future. From artificial intelligence to quantum computing, discover what lies ahead in the tech world.',
    tags: ['Technology', 'AI', 'Future', 'Innovation']
});

const relatedPosts = ref([
    {
        id: 2,
        title: 'Understanding Machine Learning',
        excerpt: 'A comprehensive guide to ML basics.',
        image: 'https://picsum.photos/seed/ml1/400/200'
    },
    {
        id: 3,
        title: 'Cloud Computing Trends',
        excerpt: 'What is shaping the cloud industry.',
        image: 'https://picsum.photos/seed/cloud1/400/200'
    }
]);

const goToPost = (postItem) => {
    router.push({ name: 'BlogPost', params: { slug: `post-${postItem.id}` } });
};

onMounted(() => {
    // In a real app, fetch post data based on route.params.slug
    console.log('Loading post:', route.params.slug);
});
</script>

<style scoped>
.prose h2 {
    margin-top: 2rem;
    margin-bottom: 1rem;
}
</style>
