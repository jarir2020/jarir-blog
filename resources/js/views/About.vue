<template>
    <AppHeader />
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">About Us</h1>
            
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-700 leading-relaxed mb-6">
                    Welcome to Jarir Blog, your trusted source for insightful articles, news, and stories from around the world.
                </p>
                
                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Our Mission</h2>
                <p class="text-gray-700 leading-relaxed mb-6">
                    We are dedicated to providing high-quality content that informs, educates, and inspires our readers. Our team of expert writers covers a wide range of topics including technology, lifestyle, business, travel, and more.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">What We Offer</h2>
                <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                    <li>In-depth articles on trending topics</li>
                    <li>Expert analysis and insights</li>
                    <li>Regular updates with fresh content</li>
                    <li>Community-driven discussions</li>
                    <li>Free access to all articles</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Our Team</h2>
                <p class="text-gray-700 leading-relaxed mb-6">
                    Our diverse team of writers and editors brings years of experience across various industries. We are passionate about delivering accurate, engaging, and valuable content to our readers.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Contact Us</h2>
                <p class="text-gray-700 leading-relaxed mb-6">
                    Have questions or suggestions? We'd love to hear from you! Visit our <router-link to="/contact" class="text-blue-600 hover:underline">contact page</router-link> to get in touch.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue';
// About page component
</script>
