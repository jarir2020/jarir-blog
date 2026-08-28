<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Phase 4 — admin paths render their own layout -->
        <router-view v-if="isAdmin" />

        <template v-else>
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <router-link to="/" class="text-2xl font-bold text-blue-600">
                            Jarir Blog
                        </router-link>
                    </div>
                    <nav class="hidden md:flex items-center space-x-6">
                        <router-link to="/" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium">Home</router-link>
                        <router-link
                            v-for="category in categories"
                            :key="category.id"
                            :to="`/category/${category.slug}`"
                            class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium"
                        >
                            {{ category.name }}
                        </router-link>
                        <router-link to="/about" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium">About</router-link>
                        <router-link to="/contact" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium">Contact</router-link>
                        <form class="ml-2" @submit.prevent="submitSearch">
                            <input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search…"
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent w-40"
                            />
                        </form>
                    </nav>
                    <div class="md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Mobile menu -->
            <div v-if="mobileMenuOpen" class="md:hidden bg-white border-t">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <router-link to="/" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Home</router-link>
                    <router-link
                        v-for="category in categories"
                        :key="category.id"
                        :to="`/category/${category.slug}`"
                        class="block px-3 py-2 text-gray-700 hover:bg-gray-100"
                    >
                        {{ category.name }}
                    </router-link>
                    <router-link to="/about" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">About</router-link>
                    <router-link to="/contact" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Contact</router-link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <router-view></router-view>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Jarir Blog</h3>
                        <p class="text-gray-400 text-sm">Your source for the latest news, insights, and stories.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li><router-link to="/" class="hover:text-white">Home</router-link></li>
                            <li><router-link to="/about" class="hover:text-white">About Us</router-link></li>
                            <li><router-link to="/contact" class="hover:text-white">Contact</router-link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-400 hover:text-white">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400 text-sm">
                    <p>
                        &copy; 2024 Jarir Blog. All rights reserved.
                        &middot;
                        <a href="/feed.xml" class="hover:text-white">RSS</a>
                    </p>
                </div>
            </div>
        </footer>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import useApi from './composables/useApi';

const route = useRoute();
const router = useRouter();
const mobileMenuOpen = ref(false);
const categories = ref([]);
const searchQuery = ref('');

const isAdmin = computed(() => route.path.startsWith('/admin'));

const api = useApi();

const submitSearch = () => {
    const q = searchQuery.value.trim();
    if (!q) return;
    router.push({ name: 'Search', query: { q } });
};

onMounted(async () => {
    try {
        const data = await api.listCategories();
        categories.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch (e) {
        // Non-fatal: header falls back to no category links.
        categories.value = [];
    }
});
</script>

<style scoped>
.router-link-active {
    color: #2563eb;
}
</style>
