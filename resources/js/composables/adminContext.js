import { reactive, readonly } from 'vue';

/**
 * Shared admin SPA state.
 *
 * AdminLayout.vue fetches the signed-in user + dashboard stats on
 * mount and stores them here. Child views (sidebar, dashboard, posts
 * list, etc.) read the same reactive object instead of re-fetching,
 * which is what kept causing flicker on the sidebar's pending-
 * comments badge.
 *
 * A single module-level store is enough — the admin SPA is one tree.
 * If the app ever grows to multiple admin roots this should be
 * promoted to a Pinia store, but a plain reactive singleton is the
 * simplest correct answer for now.
 */
const state = reactive({
    user: null,
    isAdmin: false,
    pendingComments: 0,
});

export const useAdminContext = () => state;

export const setAdminContext = (payload = {}) => {
    state.user = payload.user ?? null;
    state.isAdmin = payload.isAdmin ?? false;
    state.pendingComments = payload.pendingComments ?? 0;
};

export const clearAdminContext = () => {
    state.user = null;
    state.isAdmin = false;
    state.pendingComments = 0;
};

export const adminContextReadonly = () => readonly(state);
