<template>
    <div class="min-h-screen bg-gray-50">
        <header class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-xl font-semibold">Jarir Blog — Admin</h1>
                <nav class="flex items-center space-x-4 text-sm">
                    <router-link to="/admin" class="hover:text-blue-300" exact-active-class="text-blue-300">
                        Dashboard
                    </router-link>
                    <router-link to="/admin/posts" class="hover:text-blue-300" active-class="text-blue-300">
                        Posts
                    </router-link>
                    <router-link to="/admin/comments" class="hover:text-blue-300" active-class="text-blue-300">
                        Comments
                    </router-link>
                    <span v-if="me" class="text-gray-300">
                        {{ me.name }}
                        <span class="text-xs text-gray-500">({{ me.role }})</span>
                    </span>
                    <button
                        type="button"
                        class="ml-2 px-2 py-1 text-xs bg-gray-700 hover:bg-gray-600 rounded"
                        @click="logout"
                    >
                        Log out
                    </button>
                </nav>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const me = ref(null);

const loadMe = async () => {
    try {
        const { data } = await axios.get('/api/admin/me');
        me.value = data.user;
        if (!data.is_admin) {
            // Authenticated but not an admin — show a forbidden view, not
            // a login form (the user is already logged in).
        }
    } catch (e) {
        // Not authenticated at all. Bounce to the Breeze login page and
        // remember where the user was headed so we can send them back
        // after they sign in.
        const intended = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `/login?intended=${intended}`;
    }
};

const logout = async () => {
    try {
        await axios.post('/logout');
    } catch (e) {
        // ignore
    }
    window.location.href = '/';
};

onMounted(loadMe);
</script>
