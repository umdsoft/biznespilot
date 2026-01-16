/**
 * useSeo - Vue Composable for SEO Management
 *
 * Provides easy-to-use SEO utilities for Inertia/Vue pages
 * Works with @inertiaHead to set dynamic meta tags
 *
 * @example
 * import { useSeo } from '@/composables/useSeo';
 *
 * // In your component setup:
 * useSeo({
 *     title: 'Dashboard',
 *     description: 'Business management dashboard',
 * });
 */

import { usePage, Head } from '@inertiajs/vue3';
import { computed, h } from 'vue';

/**
 * Default SEO configuration
 */
const defaultConfig = {
    siteName: 'BiznesPilot',
    titleSeparator: ' - ',
    defaultDescription: "O'zbekistondagi #1 biznes boshqaruv platformasi",
    defaultImage: '/images/og-image.png',
    twitterHandle: '@biznespilot',
    locale: 'uz_UZ',
};

/**
 * Generate SEO meta tags
 *
 * @param {Object} options - SEO options
 * @param {string} options.title - Page title
 * @param {string} options.description - Page description
 * @param {string} options.image - OG image URL
 * @param {string} options.url - Canonical URL
 * @param {string} options.type - OG type (website, article, product)
 * @param {Object} options.article - Article metadata (author, publishedTime, etc.)
 * @param {boolean} options.noindex - Set to true to prevent indexing
 * @returns {Object} - Head component with meta tags
 */
export function useSeo(options = {}) {
    const page = usePage();

    const seo = computed(() => {
        const {
            title,
            description = defaultConfig.defaultDescription,
            image = defaultConfig.defaultImage,
            url = typeof window !== 'undefined' ? window.location.href : '',
            type = 'website',
            article = null,
            noindex = false,
            keywords = '',
        } = options;

        // Build full title
        const fullTitle = title
            ? `${title}${defaultConfig.titleSeparator}${defaultConfig.siteName}`
            : defaultConfig.siteName;

        // Get absolute image URL
        const absoluteImage = image.startsWith('http')
            ? image
            : `${typeof window !== 'undefined' ? window.location.origin : ''}${image}`;

        return {
            title: fullTitle,
            description,
            image: absoluteImage,
            url,
            type,
            article,
            noindex,
            keywords,
        };
    });

    return seo;
}

/**
 * Create Head component with SEO meta tags
 *
 * @param {Object} options - SEO options
 * @returns {VNode} - Vue Head component
 */
export function SeoHead(options = {}) {
    const seo = useSeo(options);

    return h(Head, {}, [
        // Title
        h('title', {}, seo.value.title),

        // Basic Meta
        h('meta', { name: 'description', content: seo.value.description }),
        seo.value.keywords && h('meta', { name: 'keywords', content: seo.value.keywords }),
        seo.value.noindex && h('meta', { name: 'robots', content: 'noindex, nofollow' }),

        // Open Graph
        h('meta', { property: 'og:title', content: seo.value.title }),
        h('meta', { property: 'og:description', content: seo.value.description }),
        h('meta', { property: 'og:image', content: seo.value.image }),
        h('meta', { property: 'og:url', content: seo.value.url }),
        h('meta', { property: 'og:type', content: seo.value.type }),

        // Twitter
        h('meta', { name: 'twitter:title', content: seo.value.title }),
        h('meta', { name: 'twitter:description', content: seo.value.description }),
        h('meta', { name: 'twitter:image', content: seo.value.image }),

        // Article specific (if type is article)
        seo.value.article?.author && h('meta', { property: 'article:author', content: seo.value.article.author }),
        seo.value.article?.publishedTime && h('meta', { property: 'article:published_time', content: seo.value.article.publishedTime }),
        seo.value.article?.modifiedTime && h('meta', { property: 'article:modified_time', content: seo.value.article.modifiedTime }),

        // Canonical
        h('link', { rel: 'canonical', href: seo.value.url }),
    ].filter(Boolean));
}

/**
 * Predefined SEO configurations for common pages
 */
export const seoPresets = {
    dashboard: {
        title: 'Dashboard',
        description: 'Biznes ko\'rsatkichlaringizni real vaqtda kuzating va tahlil qiling.',
        noindex: true, // Dashboard shouldn't be indexed
    },
    leads: {
        title: 'Leadlar',
        description: 'Barcha leadlaringizni bir joyda boshqaring va kuzating.',
        noindex: true,
    },
    settings: {
        title: 'Sozlamalar',
        description: 'Hisob sozlamalarini boshqaring.',
        noindex: true,
    },
    login: {
        title: 'Kirish',
        description: 'BiznesPilot hisobingizga kiring.',
        keywords: 'login, kirish, BiznesPilot',
    },
    register: {
        title: "Ro'yxatdan o'tish",
        description: "BiznesPilot da yangi hisob yarating. 14 kun bepul sinab ko'ring.",
        keywords: "register, ro'yxat, BiznesPilot, bepul CRM",
    },
};

/**
 * Get SEO preset by page name
 *
 * @param {string} pageName - Page name
 * @returns {Object} - SEO preset
 */
export function getSeoPreset(pageName) {
    return seoPresets[pageName] || {};
}

export default useSeo;
