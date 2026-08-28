<template>
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <nav class="hidden md:flex items-center space-x-6 text-sm">
                    <router-link to="/" class="text-gray-700 hover:text-blue-600 px-3 py-2 font-medium">Home</router-link>
                    <router-link
                        v-for="category in categories"
                        :key="category.id"
                        :to="`/category/${category.slug}`"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 font-medium"
                    >
                        {{ category.name }}
                    </router-link>
                    <router-link to="/about" class="text-gray-700 hover:text-blue-600 px-3 py-2 font-medium">About</router-link>
                    <router-link to="/contact" class="text-gray-700 hover:text-blue-600 px-3 py-2 font-medium">Contact</router-link>
                </nav>

                <form class="ml-2" @submit.prevent="submitSearch">
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search…"
                        class="px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent w-40"
                    />
                </form>

                <div class="md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700" aria-label="Menu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div v-if="mobileMenuOpen" class="md:hidden pb-3">
                <div class="px-2 space-y-1">
                    <router-link to="/" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">Home</router-link>
                    <router-link
                        v-for="category in categories"
                        :key="category.id"
                        :to="`/category/${category.slug}`"
                        class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"
                    >
                        {{ category.name }}
                    </router-link>
                    <router-link to="/about" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">About</router-link>
                    <router-link to="/contact" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">Contact</router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const mobileMenuOpen = ref(false);
const categories = ref([]);
const searchQuery = ref('');

const submitSearch = () => {
    const q = searchQuery.value.trim();
    if (!q) return;
    router.push({ name: 'Search', query: { q } });
};

onMounted(async () => {
    try {
        const res = await fetch('/api/categories', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        categories.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch (e) {
        categories.value = [];
    }
});
</script>
