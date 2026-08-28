<template>
    <!--
      The site header, footer, and (for /admin) sidebar are rendered
      server-side by the Blade layouts (resources/views/components/site
      and resources/views/components/admin). The Vue SPA only renders
      the page content via <router-view> so the chrome stays in one
      place and the public + admin themes can't drift apart.
    -->
    <router-view v-if="isAdmin" />
    <router-view v-else />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const isAdmin = computed(() => route.path.startsWith('/admin'));

// Sidebar state (open/close on mobile) and the category nav live in
// AppHeader.vue; the search form lives in AppSearch.vue. They're
// mounted by each public view that wants them so we don't carry the
// API call cost on pages that don't.
const categories = ref([]);

onMounted(async () => {
    if (isAdmin.value) {
        // Admin pages don't show the public category nav.
        return;
    }
    try {
        const res = await fetch('/api/categories', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        categories.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch (e) {
        categories.value = [];
    }
});

// Exposed for the views that need it. Use defineExpose so the parent
// (or any view's <script setup>) can read these refs without prop
// drilling.
defineExpose({ categories });
</script>
