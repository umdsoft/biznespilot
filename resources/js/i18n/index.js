/**
 * BiznesPilot Multi-language System
 * Supports: O'zbek (lotin), Ўзбек (кирилл), Русский
 *
 * Translations locales/ papkasidan import qilinadi:
 *   - locales/uz-latn.js
 *   - locales/uz-cyrl.js
 *   - locales/ru.js
 */

import { reactive, computed } from 'vue';
import uzLatn from './locales/uz-latn.js';
import uzCyrl from './locales/uz-cyrl.js';
import ru from './locales/ru.js';

// Available locales
export const locales = {
    'uz-latn': {
        code: 'uz-latn',
        name: 'O\'zbekcha',
        nativeName: 'O\'zbekcha',
        flag: '🇺🇿',
        dir: 'ltr',
    },
    'uz-cyrl': {
        code: 'uz-cyrl',
        name: 'Ўзбекча',
        nativeName: 'Ўзбекча',
        flag: '🇺🇿',
        dir: 'ltr',
    },
    'ru': {
        code: 'ru',
        name: 'Русский',
        nativeName: 'Русский',
        flag: '🇷🇺',
        dir: 'ltr',
    },
};

// Default locale
export const defaultLocale = 'uz-latn';

// All translations - loaded synchronously at boot
const allTranslations = {
    'uz-latn': uzLatn,
    'uz-cyrl': uzCyrl,
    'ru': ru,
};

// Get stored locale from localStorage
function getStoredLocale() {
    if (typeof window === 'undefined') return defaultLocale;

    const stored = localStorage.getItem('locale');
    if (stored && locales[stored]) return stored;

    // Try cookie
    const match = document.cookie.match(/locale=([^;]+)/);
    if (match && locales[match[1]]) return match[1];

    return defaultLocale;
}

// Save locale to localStorage and cookie
function saveLocale(locale) {
    if (typeof window === 'undefined') return;

    localStorage.setItem('locale', locale);

    // Also save to cookie (for server-side access)
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `locale=${locale};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
}

// Initialize locale immediately
const initialLocale = typeof window !== 'undefined' ? getStoredLocale() : defaultLocale;

// Reactive state - GLOBAL singleton
const state = reactive({
    currentLocale: initialLocale,
    translations: { ...allTranslations[initialLocale] },
    isLoaded: true,
});

// Log initialization (faqat development rejimida)
if (typeof window !== 'undefined' && import.meta.env.DEV) {
    console.log(`[i18n] Initialized with locale: ${state.currentLocale}`);
    console.log(`[i18n] Translations loaded: ${Object.keys(state.translations).length} keys`);
}

// Get current locale
export function getCurrentLocale() {
    return state.currentLocale;
}

// Set locale and reload page
export function setLocale(locale) {
    if (!locales[locale]) {
        console.warn(`[i18n] Invalid locale: ${locale}`);
        return;
    }

    if (import.meta.env.DEV) console.log(`[i18n] Switching to locale: ${locale}`);
    saveLocale(locale);

    // Reload page to apply new locale
    if (typeof window !== 'undefined') {
        window.location.reload();
    }
}

// Load translations (for compatibility - now sync)
export async function loadTranslations(locale = null) {
    const targetLocale = locale || state.currentLocale;

    // Update state with new translations
    state.currentLocale = targetLocale;

    // Clear and reassign translations
    const newTranslations = allTranslations[targetLocale] || allTranslations[defaultLocale];
    Object.keys(state.translations).forEach(key => delete state.translations[key]);
    Object.assign(state.translations, newTranslations);

    state.isLoaded = true;
    if (import.meta.env.DEV) console.log(`[i18n] Loaded translations for: ${targetLocale}`);

    // Also try to fetch from API for extended translations
    try {
        const response = await fetch(`/api/translations/${targetLocale}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            // Merge API translations (they have app. prefix)
            Object.entries(data).forEach(([key, value]) => {
                // Store with and without app. prefix
                state.translations[key] = value;
                if (key.startsWith('app.')) {
                    state.translations[key.replace('app.', '')] = value;
                }
            });
            if (import.meta.env.DEV) console.log(`[i18n] Merged ${Object.keys(data).length} API translations`);
        }
    } catch (e) {
        if (import.meta.env.DEV) console.log('[i18n] API fetch skipped, using bundled translations');
    }

    return state.translations;
}

// Translation function
export function t(key, replacements = {}) {
    // Try direct key
    let value = state.translations[key];

    // Try with app. prefix
    if (value === undefined && !key.startsWith('app.')) {
        value = state.translations[`app.${key}`];
    }

    // Try without app. prefix
    if (value === undefined && key.startsWith('app.')) {
        value = state.translations[key.replace('app.', '')];
    }

    if (typeof value !== 'string') {
        return key;
    }

    // Replace placeholders (:placeholder and {placeholder} formats)
    let result = value;
    for (const [placeholder, replacement] of Object.entries(replacements)) {
        result = result.replace(new RegExp(`:${placeholder}`, 'g'), String(replacement));
        result = result.replace(new RegExp(`\\{${placeholder}\\}`, 'g'), String(replacement));
    }

    return result;
}

// Vue plugin
export const i18nPlugin = {
    install(app) {
        // Global properties
        app.config.globalProperties.$t = (key, replacements = {}) => {
            return t(key, replacements);
        };

        app.config.globalProperties.$locale = {
            current: () => state.currentLocale,
            set: setLocale,
            list: locales,
        };

        // Provide for composition API
        app.provide('i18nState', state);
        app.provide('t', t);
        app.provide('locale', {
            current: computed(() => state.currentLocale),
            set: setLocale,
            list: locales,
        });
    },
};

// Composable for components
export function useI18n() {
    // Create reactive t function that re-evaluates when translations change
    const translate = (key, replacements = {}) => {
        // Access state.currentLocale to trigger reactivity
        const locale = state.currentLocale;
        const trans = state.translations;
        return t(key, replacements);
    };

    return {
        t: translate,
        locale: computed(() => state.currentLocale),
        setLocale,
        locales,
        isLoaded: computed(() => state.isLoaded),
        translations: computed(() => state.translations),
        // Direct access to state for debugging
        _state: state,
    };
}

// Export state for debugging
export { state as i18nState };
