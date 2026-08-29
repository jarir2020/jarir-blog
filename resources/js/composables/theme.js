import { reactive } from 'vue';

/**
 * Phase 7 — dark mode / light mode.
 *
 * The class on `<html>` is set by the FOUC-prevention inline
 * script in resources/views/components/site/site-layout.blade.php
 * (and the admin layout) *before* the body renders. This module
 * reads / writes that class plus the localStorage key the inline
 * script also reads, so a click on the topbar toggle and a page
 * reload stay in sync.
 *
 * Storage:
 *   - `localStorage.theme === 'light' | 'dark'` — explicit user choice.
 *   - `localStorage.theme === null`              — follow `prefers-color-scheme`.
 *
 *   We use 'light' | 'dark' as the only stored values; 'system' is
 *   modelled as the absence of a stored value. The composable
 *   accepts a third value at the call site (`setTheme('system')`)
 *   so a future 3-way picker can be added without a breaking change.
 */
const STORAGE_KEY = 'theme';

const state = reactive({
    theme: detectTheme(),
});

function detectTheme() {
    if (typeof window === 'undefined') return 'light';
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'light' || saved === 'dark') return saved;
    } catch (e) {
        // localStorage may throw in privacy modes; fall through.
    }
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

function applyTheme(theme) {
    if (typeof document === 'undefined') return;
    document.documentElement.classList.toggle('dark', theme === 'dark');
}

function setTheme(theme) {
    if (theme === 'system') {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        theme = detectTheme();
    } else if (theme === 'light' || theme === 'dark') {
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
    } else {
        return; // unknown value, no-op
    }
    state.theme = theme;
    applyTheme(theme);
}

function toggleTheme() {
    setTheme(state.theme === 'dark' ? 'light' : 'dark');
}

export function useTheme() {
    return {
        theme: state.theme,
        setTheme,
        toggleTheme,
    };
}

// Expose the toggle on `window` so the inline click handler in the
// topbar can reach it without a Vue bridge. The function is small
// enough that wiring a Vue plugin for one click would be overkill.
if (typeof window !== 'undefined') {
    window.__theme = { toggle: toggleTheme, set: setTheme };
}
