<template>
    <!--
      The dark sidebar + white topbar are rendered server-side by the
      x-admin-layout Blade component (resources/views/admin.blade.php).
      This Vue component is invisible: it just bootstraps the SPA by
      fetching the signed-in user + dashboard stats, exposes them to
      child views via the adminContext store, and renders the matched
      route. That way the Vue SPA and the server-rendered fallback
      share the same chrome tokens.
    -->
    <router-view />
</template>

<script setup>
import axios from 'axios';
import { onMounted } from 'vue';
import { setAdminContext } from '../../composables/adminContext';

const loadShell = async () => {
    try {
        const [meRes, statsRes] = await Promise.all([
            axios.get('/api/admin/me'),
            axios.get('/api/admin/stats'),
        ]);
        setAdminContext({
            user: meRes.data.user,
            isAdmin: meRes.data.is_admin,
            pendingComments: statsRes.data.comments?.pending ?? 0,
        });
    } catch (e) {
        // Not authenticated. Bounce to the Breeze login page.
        const intended = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `/login?intended=${intended}`;
    }
};

onMounted(loadShell);
</script>
