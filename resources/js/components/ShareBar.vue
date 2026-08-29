<template>
    <div class="flex items-center gap-2 text-sm">
        <span v-if="label" class="text-gray-500 mr-1">{{ label }}</span>

        <a
            :href="facebookUrl"
            target="_blank"
            rel="noopener"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700"
            aria-label="Share on Facebook"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22 12a10 10 0 10-11.6 9.87v-6.98H7.9V12h2.5V9.8c0-2.47 1.48-3.84 3.74-3.84 1.08 0 2.21.2 2.21.2v2.43h-1.25c-1.23 0-1.61.76-1.61 1.55V12h2.74l-.44 2.89h-2.3v6.98A10 10 0 0022 12z"/>
            </svg>
        </a>

        <a
            :href="twitterUrl"
            target="_blank"
            rel="noopener"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-black text-white hover:bg-gray-800"
            aria-label="Share on X (Twitter)"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.244 2H21l-6.51 7.44L22 22h-6.86l-4.79-6.27L4.7 22H2l6.96-7.96L2 2h6.95l4.33 5.72L18.244 2zm-2.41 18.2h1.83L7.27 3.7H5.31l10.523 16.5z"/>
            </svg>
        </a>

        <a
            :href="linkedinUrl"
            target="_blank"
            rel="noopener"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-blue-700 text-white hover:bg-blue-800"
            aria-label="Share on LinkedIn"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.36V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.26 2.37 4.26 5.45v6.29zM5.34 7.43a2.06 2.06 0 110-4.13 2.06 2.06 0 010 4.13zM7.12 20.45H3.56V9h3.56v11.45z"/>
            </svg>
        </a>

        <a
            :href="whatsappUrl"
            target="_blank"
            rel="noopener"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-green-500 text-white hover:bg-green-600"
            aria-label="Share on WhatsApp"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.86 9.86 0 004.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.91-7.01A9.83 9.83 0 0012.04 2zm5.45 14.13c-.23.65-1.36 1.25-1.86 1.3-.47.05-1.07.07-1.73-.11-.4-.11-.91-.27-1.57-.55-2.76-1.19-4.56-3.97-4.7-4.16-.14-.18-1.12-1.49-1.12-2.84 0-1.35.71-2.02.96-2.29.25-.27.55-.34.73-.34.18 0 .36 0 .51.01.17.01.39-.06.61.47.23.55.78 1.93.85 2.07.07.14.11.3.02.48-.09.18-.14.3-.27.46-.14.16-.29.36-.41.48-.14.14-.28.29-.12.57.16.27.71 1.17 1.52 1.9 1.04.93 1.92 1.22 2.19 1.36.27.14.43.11.59-.07.16-.18.69-.81.87-1.08.18-.27.36-.23.61-.14.25.09 1.62.76 1.9.9.28.14.46.2.53.32.07.11.07.66-.16 1.31z"/>
            </svg>
        </a>

        <button
            type="button"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300 relative"
            :aria-label="copied ? 'Link copied' : 'Copy link'"
            @click="copy"
        >
            <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.656-5.656m-3.656 3.656a4 4 0 01-5.656-5.656l3-3a4 4 0 015.656 5.656"/>
            </svg>
            <svg v-else class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span
                v-if="copied"
                class="absolute -top-7 left-1/2 -translate-x-1/2 text-xs bg-gray-900 text-white px-2 py-1 rounded whitespace-nowrap"
            >Copied!</span>
        </button>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    url: { type: String, default: '' },
    title: { type: String, default: '' },
    label: { type: String, default: '' },
});

const copied = ref(false);

const shareUrl = computed(() => {
    if (props.url) return props.url;
    if (typeof window !== 'undefined') return window.location.href;
    return '';
});

const shareText = computed(() => props.title || (typeof document !== 'undefined' ? document.title : ''));

const facebookUrl = computed(
    () => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`,
);
const twitterUrl = computed(
    () => `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
);
const linkedinUrl = computed(
    () => `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl.value)}`,
);
const whatsappUrl = computed(
    () => `https://wa.me/?text=${encodeURIComponent(shareText.value + ' ' + shareUrl.value)}`,
);

const copy = async () => {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch (e) {
        // Clipboard API blocked (e.g. insecure context). Silently
        // fall back to a manual select-and-copy prompt.
        const ta = document.createElement('textarea');
        ta.value = shareUrl.value;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); copied.value = true; setTimeout(() => (copied.value = false), 1500); } catch (_) {}
        document.body.removeChild(ta);
    }
};
</script>
