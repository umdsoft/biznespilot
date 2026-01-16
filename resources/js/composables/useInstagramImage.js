/**
 * Instagram image handling composable
 * Handles CDN URL expiration and provides fallback images
 */

import { ref } from 'vue';

// SVG placeholder as data URI for Instagram posts
const PLACEHOLDER_IMAGE = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIiB2aWV3Qm94PSIwIDAgNDAwIDQwMCI+PHJlY3QgZmlsbD0iIzFmMjkzNyIgd2lkdGg9IjQwMCIgaGVpZ2h0PSI0MDAiLz48ZyBmaWxsPSIjNGI1NTYzIj48cGF0aCBkPSJNMjAwIDEwMGMtNTUuMjMgMC0xMDAgNDQuNzctMTAwIDEwMHM0NC43NyAxMDAgMTAwIDEwMCAxMDAtNDQuNzcgMTAwLTEwMC00NC43Ny0xMDAtMTAwLTEwMHptMCAxNjBjLTMzLjE0IDAtNjAtMjYuODYtNjAtNjBzMjYuODYtNjAgNjAtNjAgNjAgMjYuODYgNjAgNjAtMjYuODYgNjAtNjAgNjB6Ii8+PGNpcmNsZSBjeD0iMzEwIiBjeT0iOTAiIHI9IjI1Ii8+PC9nPjx0ZXh0IHg9IjIwMCIgeT0iMzIwIiBmaWxsPSIjNmI3MjgwIiBmb250LWZhbWlseT0ic3lzdGVtLXVpIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5SYXNtIG1hdmp1ZCBlbWFzPC90ZXh0Pjwvc3ZnPg==';

// Track failed images to avoid repeated loading
const failedImages = ref(new Set());

/**
 * Handle image load error - replace with placeholder
 * @param {Event} event - The error event
 */
export function handleImageError(event) {
    const img = event.target;
    const originalSrc = img.getAttribute('data-original-src') || img.src;

    // Mark as failed
    failedImages.value.add(originalSrc);

    // Set placeholder
    img.src = PLACEHOLDER_IMAGE;
    img.classList.add('image-failed');
}

/**
 * Get safe image source - returns placeholder if image previously failed
 * @param {string} url - The image URL
 * @returns {string} - Safe URL to use
 */
export function getSafeImageSrc(url) {
    if (!url || failedImages.value.has(url)) {
        return PLACEHOLDER_IMAGE;
    }
    return url;
}

/**
 * Check if an image URL is likely expired
 * Instagram CDN URLs contain expiration timestamps
 * @param {string} url - The image URL
 * @returns {boolean}
 */
export function isImageExpired(url) {
    if (!url) return true;

    // Check for common Instagram CDN patterns
    if (url.includes('scontent') && url.includes('cdninstagram')) {
        // Try to extract expiration from URL params
        try {
            const urlObj = new URL(url);
            const se = urlObj.searchParams.get('_nc_ht');
            // If URL has timestamp params, it might be expired
            return false; // Can't reliably determine without making request
        } catch {
            return false;
        }
    }

    return false;
}

/**
 * Clear failed images cache (useful when refreshing data)
 */
export function clearFailedImagesCache() {
    failedImages.value.clear();
}

/**
 * Vue directive for handling image errors
 * Usage: v-instagram-image
 */
export const vInstagramImage = {
    mounted(el) {
        if (el.tagName === 'IMG') {
            el.setAttribute('data-original-src', el.src);
            el.addEventListener('error', handleImageError);

            // Add loading placeholder
            if (!el.complete) {
                el.classList.add('image-loading');
                el.addEventListener('load', () => {
                    el.classList.remove('image-loading');
                }, { once: true });
            }
        }
    },
    unmounted(el) {
        if (el.tagName === 'IMG') {
            el.removeEventListener('error', handleImageError);
        }
    }
};

/**
 * Composable export
 */
export function useInstagramImage() {
    return {
        handleImageError,
        getSafeImageSrc,
        isImageExpired,
        clearFailedImagesCache,
        PLACEHOLDER_IMAGE,
        failedImages
    };
}

export default useInstagramImage;
