<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="bg-blue-600 rounded-lg shadow-lg p-8 mb-12 text-white">
            <h1 class="text-4xl font-bold mb-4">Welcome to Jarir Blog</h1>
            <p class="text-lg opacity-90">Discover insightful articles, news, and stories from around the world.</p>
        </div>

        <!-- Featured Posts -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="post in featuredPosts" :key="post.id" 
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
        </div>

        <!-- Latest Posts -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Latest Posts</h2>
            <div class="space-y-6">
                <div v-for="post in latestPosts" :key="post.id"
                     class="bg-white rounded-lg shadow-md p-6 flex flex-col md:flex-row gap-6 hover:shadow-lg transition-shadow cursor-pointer"
                     @click="goToPost(post)">
                    <img :src="post.image" :alt="post.title" class="w-full md:w-48 h-32 object-cover rounded">
                    <div class="flex-1">
                        <span class="text-sm text-blue-600 font-semibold">{{ post.category }}</span>
                        <h3 class="text-lg font-bold text-gray-900 mt-1 mb-2">{{ post.title }}</h3>
                        <p class="text-gray-600 text-sm">{{ post.excerpt }}</p>
                        <div class="mt-3 flex items-center gap-4">
                            <span class="text-xs text-gray-500">{{ post.author }}</span>
                            <span class="text-xs text-gray-500">{{ post.date }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const featuredPosts = ref([
    {
        id: 1,
        title: 'The Future of Technology in 2024',
        excerpt: 'Explore the emerging technologies that will shape our future.',
        category: 'Technology',
        date: 'Dec 15, 2024',
        image: 'https://picsum.photos/seed/tech1/400/200'
    },
    {
        id: 2,
        title: 'Healthy Living Tips for Busy Professionals',
        excerpt: 'Simple ways to maintain a healthy lifestyle despite a busy schedule.',
        category: 'Lifestyle',
        date: 'Dec 14, 2024',
        image: 'https://picsum.photos/seed/health1/400/200'
    },
    {
        id: 3,
        title: 'Travel Destinations You Must Visit',
        excerpt: 'Discover breathtaking places around the world.',
        category: 'Travel',
        date: 'Dec 13, 2024',
        image: 'https://picsum.photos/seed/travel1/400/200'
    }
]);

const latestPosts = ref([
    {
        id: 4,
        title: 'Understanding Artificial Intelligence',
        excerpt: 'A beginner-friendly guide to AI and machine learning concepts.',
        category: 'Technology',
        author: 'John Doe',
        date: 'Dec 12, 2024',
        image: 'https://picsum.photos/seed/ai1/400/200'
    },
    {
        id: 5,
        title: 'Best Practices for Remote Work',
        excerpt: 'Tips and tricks for staying productive while working from home.',
        category: 'Business',
        author: 'Jane Smith',
        date: 'Dec 11, 2024',
        image: 'https://picsum.photos/seed/work1/400/200'
    },
    {
        id: 6,
        title: 'Cooking Made Easy: Quick Recipes',
        excerpt: 'Delicious meals you can prepare in under 30 minutes.',
        category: 'Food',
        author: 'Mike Johnson',
        date: 'Dec 10, 2024',
        image: 'https://picsum.photos/seed/food1/400/200'
    }
]);

const goToPost = (post) => {
    router.push({ name: 'BlogPost', params: { slug: `post-${post.id}` } });
};
</script>
