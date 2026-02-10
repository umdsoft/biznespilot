import { computed, ref, watchEffect } from 'vue'

function getCookie(name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts.pop().split(';').shift()
    return null
}

// Reactive locale — updates when cookie changes (e.g. after language switch redirect)
const _locale = ref(getCookie('landing_locale') || 'uz-latn')

// Re-check cookie on visibility change (tab re-focus after redirect)
if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            _locale.value = getCookie('landing_locale') || 'uz-latn'
        }
    })
}

/**
 * Composable for landing page translations.
 * Usage:
 *   import translations from './landing-page'
 *   const { locale, t } = useLandingLocale(translations)
 *   // in template: {{ t.hero.title }}
 */
export function useLandingLocale(translations) {
    // Re-read cookie each time composable is called (handles redirect)
    _locale.value = getCookie('landing_locale') || 'uz-latn'

    const locale = computed(() => _locale.value)

    const t = computed(() => {
        return translations[locale.value] || translations['uz-latn']
    })

    return { locale, t }
}
