<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                {{ page?.title || 'Contact Us' }}
            </h1>

            <article
                v-if="page"
                class="prose prose-lg dark:prose-invert max-w-none mb-8"
                v-html="page.body_html"
            ></article>

            <p v-if="error" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-700 dark:text-red-300">{{ error }}</p>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                        <input
                            type="text"
                            id="name"
                            v-model="form.name"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Your name"
                            required
                        >
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input
                            type="email"
                            id="email"
                            v-model="form.email"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="your@email.com"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                    <input
                        type="text"
                        id="subject"
                        v-model="form.subject"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="What is this about?"
                        required
                    >
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="6"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Your message..."
                        required
                    ></textarea>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="w-full md:w-auto px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        {{ isSubmitting ? 'Sending...' : 'Send Message' }}
                    </button>
                </div>
            </form>

            <!-- Contact Info — kept as static chrome; admins can edit
                 it later by making these fields part of a "Contact
                 details" widget if they need to change the address
                 or phone. -->
            <div class="mt-12 pt-8 border-t">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">Other Ways to Reach Us</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Email</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ contactEmail }}</p>
                        </div>
                    </div>
                    <div v-if="contactAddress" class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Address</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm whitespace-pre-line">{{ contactAddress }}</p>
                        </div>
                    </div>
                    <div v-if="contactPhone" class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Phone</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ contactPhone }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import useApi from '../composables/useApi';

const api = useApi();
const page = ref(null);
const error = ref(null);

// Phase 10 — admin-editable contact details. The other
// ways-to-reach-us block reads these from /api/site-settings.
// Address and phone are hidden when blank so admins can opt out
// of either field.
const contactEmail = ref('');
const contactAddress = ref('');
const contactPhone = ref('');

// The contact intro paragraph is admin-editable via the Pages
// system. If the fetch fails (network, 404 during a transient
// deploy) we keep the form working and just show a generic title.
onMounted(async () => {
    try {
        const [pageData, settingsData] = await Promise.all([
            api.getPage('contact').catch(() => null),
            api.getSiteSettings().catch(() => ({ settings: {} })),
        ]);
        if (pageData) page.value = pageData.page;
        const s = settingsData?.settings ?? {};
        contactEmail.value = s['contact.email'] ?? 'contact@example.com';
        contactAddress.value = s['contact.address'] ?? '';
        contactPhone.value = s['contact.phone'] ?? '';
    } catch (e) {
        error.value = null; // soft-fail; the form below is the page's real value
    }
});

const isSubmitting = ref(false);
const form = ref({
    name: '',
    email: '',
    subject: '',
    message: ''
});

const submitForm = async () => {
    isSubmitting.value = true;
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    console.log('Form submitted:', form.value);
    alert('Thank you for your message! We will get back to you soon.');
    form.value = {
        name: '',
        email: '',
        subject: '',
        message: ''
    };
    isSubmitting.value = false;
};
</script>
