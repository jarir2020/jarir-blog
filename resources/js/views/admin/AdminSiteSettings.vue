<template>
    <div>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Site Settings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Site-wide content used across the chrome (site name, tagline, contact details).
            </p>
        </div>

        <p v-if="error" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
        <p v-if="success" class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-700 dark:text-green-300">{{ success }}</p>

        <form class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 max-w-2xl" @submit.prevent="save">
            <fieldset class="space-y-4">
                <legend class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Site</legend>

                <div>
                    <label for="s-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site name</label>
                    <input
                        id="s-name"
                        v-model="form['site.name']"
                        type="text"
                        required
                        maxlength="80"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Shown in the masthead logo, the footer, the browser tab title, and the RSS feed title.
                    </p>
                </div>

                <div>
                    <label for="s-tagline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tagline</label>
                    <textarea
                        id="s-tagline"
                        v-model="form['site.tagline']"
                        rows="2"
                        maxlength="500"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Default Open Graph description, RSS feed subtitle, and the footer blurb.
                    </p>
                </div>
            </fieldset>

            <fieldset class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <legend class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Loading &amp; fallbacks</legend>

                <div>
                    <label for="s-loading" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Loading message</label>
                    <input
                        id="s-loading"
                        v-model="form['site.loading_message']"
                        type="text"
                        maxlength="200"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Short placeholder shown while the Vue SPA bundle is loading.</p>
                </div>

                <div>
                    <label for="s-nojs" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No-JS fallback</label>
                    <textarea
                        id="s-nojs"
                        v-model="form['site.no_js_message']"
                        rows="2"
                        maxlength="500"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Shown inside &lt;noscript&gt; to visitors with JavaScript disabled.</p>
                </div>

                <div>
                    <label for="s-theme-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Browser theme color</label>
                    <div class="flex items-center gap-2 max-w-md">
                        <input
                            id="s-theme-color"
                            v-model="form['site.theme_color']"
                            type="color"
                            class="h-10 w-12 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer"
                        />
                        <input
                            v-model="form['site.theme_color']"
                            type="text"
                            maxlength="9"
                            pattern="^#[0-9a-fA-F]{6}$"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md font-mono text-sm"
                        />
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hex (e.g. #ffffff) for the browser's address-bar color on mobile.</p>
                </div>
            </fieldset>

            <fieldset class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <legend class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Contact</legend>

                <div>
                    <label for="s-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input
                        id="s-email"
                        v-model="form['contact.email']"
                        type="email"
                        required
                        maxlength="160"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                </div>

                <div>
                    <label for="s-address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Address <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea
                        id="s-address"
                        v-model="form['contact.address']"
                        rows="2"
                        maxlength="500"
                        placeholder="123 Blog Street&#10;Dhaka, Bangladesh"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Multi-line is fine. Leave blank to hide the address block on /contact.</p>
                </div>

                <div>
                    <label for="s-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Phone <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input
                        id="s-phone"
                        v-model="form['contact.phone']"
                        type="tel"
                        maxlength="80"
                        placeholder="+880 1234 567890"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave blank to hide the phone block on /contact.</p>
                </div>
            </fieldset>

            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    :disabled="saving"
                    class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                >{{ saving ? 'Saving…' : 'Save' }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const form = ref({
    'site.name': '',
    'site.tagline': '',
    'site.no_js_message': '',
    'site.loading_message': '',
    'site.theme_color': '',
    'contact.email': '',
    'contact.address': '',
    'contact.phone': '',
});
const saving = ref(false);
const error = ref(null);
const success = ref(null);

const load = async () => {
    try {
        const { data } = await axios.get('/api/admin/site-settings');
        // Merge so the form always has every known key, even if
        // the backend hasn't seeded all of them yet.
        form.value = { ...form.value, ...(data.settings ?? {}) };
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Failed to load settings.';
    }
};

const save = async () => {
    error.value = null;
    success.value = null;
    saving.value = true;
    try {
        await axios.put('/api/admin/site-settings', { settings: form.value });
        success.value = 'Settings saved.';
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat()[0] ?? 'Could not save settings.';
        } else {
            error.value = e?.response?.data?.message ?? 'Could not save settings.';
        }
    } finally {
        saving.value = false;
    }
};

onMounted(load);
</script>
