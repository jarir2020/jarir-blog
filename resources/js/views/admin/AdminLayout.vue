<template>
    <div class="min-h-screen bg-gray-50 flex">
        <AdminSidebar :pending-comments="pendingComments" />

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-end gap-4">
                <div v-if="me" class="text-sm text-gray-700">
                    {{ me.name }}
                    <span class="text-xs text-gray-500 ml-1">({{ me.role }})</span>
                </div>
                <button
                    type="button"
                    class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                    @click="logout"
                >
                    Log out
                </button>
            </header>

            <main class="flex-1 px-6 py-8 max-w-7xl w-full">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import AdminSidebar from '../../components/admin/AdminSidebar.vue';

const me = ref(null);
const pendingComments = ref(0);

const loadShell = async () => {
    try {
        const [meRes, statsRes] = await Promise.all([
            axios.get('/api/admin/me'),
            axios.get('/api/admin/stats'),
        ]);
        me.value = meRes.data.user;
        pendingComments.value = statsRes.data.comments?.pending ?? 0;
        if (!meRes.data.is_admin) {
            // Authenticated but not an admin — render the page anyway
            // (the API will reject any actual action). Don't bounce.
        }
    } catch (e) {
        // Not authenticated. Bounce to the Breeze login page and remember
        // where the user was headed.
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

onMounted(loadShell);
</script>
